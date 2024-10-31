<?php

class WP_P4FRestAPI
{

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->options = get_option('p4f_options');
        $this->crypto = new P4FWC_DataEncryption();
        $this->table = $this->wpdb->prefix . 'p4f_donations';
    }

    public function init()
    {
        add_action('rest_api_init', [$this, 'add_p4f_donate_return_success_endpoint']);
        add_action('rest_api_init', [$this, 'add_p4f_donate_return_failure_endpoint']);
        add_action('rest_api_init', [$this, 'add_p4f_donate_return_confirmation_endpoint']);
        add_action('rest_api_init', [$this, 'add_p4f_wocommerce_return_confirmation_endpoint']);
        add_action('rest_api_init', [$this, 'add_p4f_donate_endpoint']);
        add_action('rest_api_init', [$this, 'add_p4f_clear_donations_endpoint']);
        add_action('rest_api_init', [$this, 'add_p4f_clear_logs']);
    }

    public function add_p4f_clear_logs()
    {
        register_rest_route('p4f-plugin/v1', '/logs/clear', [
            'methods' => 'GET',
            'callback' => [$this, 'p4f_clear_logs'],
            'permission_callback' => '__return_true'
        ]);
    }

    function p4f_clear_logs(WP_REST_Request $request)
    {
        P4FWC_LogViewer::clearLogs();
        wp_redirect($request->get_param('return_url'));
        //echo $request->get_param('return_url');
        exit;
    }

    public function add_p4f_clear_donations_endpoint()
    {
        register_rest_route('p4f-plugin/v1', '/donations/clear', [
            'methods' => 'GET',
            'callback' => [$this, 'p4f_donate_clear_table'],
            'permission_callback' => '__return_true'
        ]);
    }

    function p4f_donate_clear_table(WP_REST_Request $request)
    {
        self::writeLog('WP_P4FRestAPI::p4f_donate_clear_table() ==> ' . print_r($request->get_params(), true));

        //confirm that this request was sent by the admin panel
        $params = $request->get_params();
        $merchant_id = $this->crypto->decrypt($params['token']);

        //echo $merchant_id;
        //echo $this->crypto->decrypt($this->options['merchant_id']);

        if ($merchant_id === $this->crypto->decrypt($this->options['merchant_id'])) {
            $result = $this->wpdb->query("TRUNCATE TABLE " . $this->table);
            if ($result === false) {
                self::writeLog(htmlspecialchars_decode($this->wpdb->last_query, ENT_QUOTES));
                self::writeLog(htmlspecialchars_decode($this->wpdb->last_error, ENT_QUOTES));
                http_response_code(500);
                echo json_encode(['status' => 'ERROR']);
                exit();
            }
            http_response_code(200);
            echo json_encode(['status' => 'OK']);
            exit();
        }
        http_response_code(400);
        exit();
    }


    public function add_p4f_donate_return_success_endpoint()
    {
        register_rest_route('p4f-plugin/v1', '/return/success', [
            'methods' => 'GET',
            'callback' => [$this, 'p4f_donate_return_success'],
            'permission_callback' => '__return_true'
        ]);
    }

    function p4f_donate_return_success(WP_REST_Request $request)
    {
        self::writeLog('WP_P4FRestAPI::p4f_donate_return_success() ==> ' . print_r($request->get_params(), true));
        wp_redirect(get_permalink($this->options['donate_ok_page_id']));
        exit;
    }


    public function add_p4f_donate_return_failure_endpoint()
    {
        register_rest_route('p4f-plugin/v1', '/return/failure', [
            'methods' => 'GET',
            'callback' => [$this, 'p4f_donate_return_failure'],
            'permission_callback' => '__return_true'
        ]);
    }

    function p4f_donate_return_failure(WP_REST_Request $request)
    {
        self::writeLog('WP_P4FRestAPI::p4f_donate_return_failure() ==> ' . print_r($request->get_params(), true));
        $params = $request->get_params();

        $status = 'Abandoned';
        if (isset($params['motive'])) {
            if ($params['motive'] == "declined_by_customer") $status = 'Customer Declined';
            $status .= ' (' . $params['motive'] . ')';
        }

        $data = ['status' => $status];
        $where = ['order_number' => $params['order_number']];

        $result = $this->wpdb->update($this->table, $data, $where);
        if ($result === false) {
            self::writeLog(htmlspecialchars_decode($this->wpdb->last_query, ENT_QUOTES));
            self::writeLog(htmlspecialchars_decode($this->wpdb->last_error, ENT_QUOTES));
        }

        wp_redirect((!empty($this->options['donate_nok_page_id']) ? get_permalink($this->options['donate_nok_page_id']) : $params['returnURL']));
        exit;
    }



    public function add_p4f_donate_return_confirmation_endpoint()
    {
        register_rest_route('p4f-plugin/v1', '/return/confirmation', [
            'methods' => 'POST',
            'callback' => [$this, 'p4f_donate_return_confirmation'],
            'permission_callback' => '__return_true'
        ]);
    }

    function p4f_donate_return_confirmation(WP_REST_Request $request)
    {
        self::writeLog('WP_P4FRestAPI::p4f_donate_return_confirmation() ==> ' . print_r($request->get_params(), true));

        self::writeLog('Pay4Fun Confirmation Data');
        $body = $request->get_body();
        self::writeLog(print_r($body, true));

        $response = json_decode($body, true);
        //writeLog("Confirmation Data: " . print_r($response, true));

        $this->wpdb->show_errors();
        $data = [
            'status' => 'Confirmed',
            'donator_name' => $response['CustomerEmail'],
            'transaction_id' => $response['TransactionId']
        ];
        $where = ['order_number' => $response['MerchantInvoiceId']];
        if (!$this->wpdb->update($this->table, $data, $where)) {
            echo htmlspecialchars_decode($this->wpdb->last_query, ENT_QUOTES);
            echo htmlspecialchars_decode($this->wpdb->last_error, ENT_QUOTES);
            self::writeLog("ERROR: Unable to update p4f donations on confirmation reponse");
            self::writeLog(htmlspecialchars_decode($this->wpdb->last_query, ENT_QUOTES));
            self::writeLog(htmlspecialchars_decode($this->wpdb->last_error, ENT_QUOTES));
            return new WP_Error('p4f-confirm-error', __('There was an error processing p4f confirmation data', 'woocommerce-pay4fun'), array('status' => 500));
        }
        exit;
    }


    public function add_p4f_wocommerce_return_confirmation_endpoint()
    {
        register_rest_route('p4f-plugin/v1', '/wc-confirmation', [
            'methods' => 'POST',
            'callback' => [$this, 'p4f_wocommerce_return_confirmation'],
            'permission_callback' => '__return_true'
        ]);
    }

    function p4f_wocommerce_return_confirmation(WP_REST_Request $request)
    {
        self::writeLog('WP_P4FRestAPI::p4f_wocommerce_return_confirmation() ==> ' . print_r($request->get_params(), true));

        $params = $request->get_params();

        $response = wp_remote_post($params['returnURL'], array(
            'method'   => 'POST',
            'timeout'  => 45,
            'headers'  => array('Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8'),
            'body'     => $params
        ));

        self::writeLog("HTTP REQUEST:");
        self::writeLog(print_r($request, true));
        self::writeLog("HTTP RESPONSE:");
        self::writeLog(print_r($response, true));


        http_response_code(wp_remote_retrieve_response_code($response));
        exit;
    }



    public function add_p4f_donate_endpoint()
    {
        register_rest_route('p4f-plugin/v1', '/donate', [
            'methods' => 'GET',
            'callback' => [$this, 'p4f_donate'],
            'permission_callback' => '__return_true'
        ]);
    }

    function p4f_donate(WP_REST_Request $request)
    {
        self::writeLog('WP_P4FRestAPI::p4f_donate() ==> ' . print_r($request->get_params(), true));

        $p4fDonateValue = filter_var($request->get_param('p4fDonateValue'), FILTER_SANITIZE_NUMBER_FLOAT);
        $p4fReturnURL = filter_var($request->get_param('p4fReturnUrl'), FILTER_SANITIZE_URL);
        $order_number = $this->options['order_prefix'] . substr(strtoupper(bin2hex(random_bytes(10))), 0, 30);
        $p4fFailedURL = filter_var(get_site_url() . "/wp-json/p4f-plugin/v1/return/failure/?returnURL={$request->get_param('p4fReturnUrl')}&order_number={$order_number}", FILTER_SANITIZE_URL);
        $p4fSuccessURL = filter_var(get_site_url() . "/wp-json/p4f-plugin/v1/return/success/?returnURL={$request->get_param('p4fReturnUrl')}&order_number={$order_number}", FILTER_SANITIZE_URL);
        $p4fConfirmationURL = filter_var(get_site_url() . '/wp-json/p4f-plugin/v1/return/confirmation', FILTER_SANITIZE_URL);

        $mode = ($this->options['sandbox_mode'] === "1") ? Pay4Fun::MODE_DONATION_SANDBOX : Pay4Fun::MODE_DONATION_LIVE;
        $debug = ($this->options['enable_debug'] === "1");

        $p4f = new Pay4Fun($mode, $debug);
        $p4f->setCredentials(
            $this->crypto->decrypt($this->options['merchant_id']),
            $this->crypto->decrypt($this->options['merchant_secret']),
            $this->crypto->decrypt($this->options['merchant_key'])
        );

        $request = [
            'amount' => $p4fDonateValue,
            'merchantInvoiceId' => $order_number,
            'language' => $this->options['language'],
            'currency' => $this->options['currency'],
            //'p4fAccountEmail' => 'test_customer@p4f.com',
            'okUrl' => $p4fSuccessURL,
            'notOkUrl' => $p4fFailedURL,
            'confirmationUrl' => $p4fConfirmationURL,
            'description' => substr($this->options['description'], 0, 40)
        ];
        if (!empty($this->options['merchant_logo'])) $request['merchantLogo'] = $this->options['merchant_logo'];
        self::writeLog("DONATE REQUEST:" . print_r($request, true));

        $result = $p4f->payIn($request);
        self::writeLog("RESPONSE FROM PAY4FUN: " . print_r($result, true));
        if (is_array($result) && isset($result['success']) && $result['success'] === true) {
            if (!$this->wpdb->insert($this->wpdb->prefix . 'p4f_donations', [
                'time' => current_time('mysql'),
                'transaction_id' => '',
                'donator_name' => '',
                'order_number' => $order_number,
                'amount' => $request['amount'],
                'status' => 'Pending',
                'p4f_url' => $result['url'],
                'origin_url' => $p4fReturnURL,
            ])) {
                //echo htmlspecialchars_decode($this->wpdb->last_query, ENT_QUOTES);
                //echo htmlspecialchars_decode($this->wpdb->last_error, ENT_QUOTES);
                self::writeLog("ERROR: Unable to update p4f donations on confirmation reponse");
                self::writeLog(htmlspecialchars_decode($this->wpdb->last_query, ENT_QUOTES));
                self::writeLog(htmlspecialchars_decode($this->wpdb->last_error, ENT_QUOTES));

                return new WP_Error('p4f-addnew-error', __('There was an error inserting p4f new transaction data', 'woocommerce-pay4fun'), array('status' => 500));
            }
            wp_redirect($result['url']);
            exit;
        }
        wp_redirect($p4fFailedURL);
        exit;
    }


    private static function writeLog($message)
    {
        $log = fopen(realpath(dirname(__FILE__)) . '/debug.log', 'a');
        fwrite($log, date('D M j G:i:s T Y') . ': ' . $message . "\n");
        fclose($log);
    }
}
