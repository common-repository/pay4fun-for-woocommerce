<?php

/**
 * Gateway class
 *
 * @package WooCommerce_pay4fun/Classes/Gateway
 * @version 2.15.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway.
 */
class WC_Pay4Fun_Gateway extends WC_Payment_Gateway
{
    private $crypto;
    private $log;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id                 = 'pay4fun';
        $this->icon               = apply_filters('woocommerce_pay4fun_icon', plugins_url('assets/images/pay4fun.png', plugin_dir_path(__FILE__)));
        $this->method_title       = __('Pay4Fun', 'woocommerce-pay4fun');
        $this->method_description = __('Accept payments by using your Pay4Fun wallet to transfer funds.', 'woocommerce-pay4fun');
        $this->order_button_text  = __('Proceed to payment', 'woocommerce-pay4fun');

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables.
        $this->crypto            = new P4FWC_DataEncryption();
        $this->title             = $this->get_option('title');
        $this->description       = $this->get_option('description');
        $this->complete_order    = $this->get_option('complete_order');
        $this->debug             = $this->get_option('debug');
        $this->merchant_id       = $this->get_id();
        $this->merchant_key      = $this->get_key();
        $this->merchant_secret   = $this->get_secret();
        $this->order_prefix      = $this->get_option('order_prefix');
        $this->language          = $this->get_option('language');
        $this->sandbox           = $this->get_option('sandbox');
        $this->merchant_logo     = $this->get_logo();

        // Active logs.
        if ('yes' === $this->debug) {
            if (function_exists('wc_get_logger')) {
                $this->log = wc_get_logger();
            } else {
                $this->log = new WC_Logger();
            }
        }

        // Main actions.
        add_action('woocommerce_api_' . $this->id, array($this, 'ipn_handler'));
        add_action('woocommerce_api_wc_pay4fun_gateway', array($this, 'ipn_handler'));
        add_action('valid_pay4fun_ipn_request', array($this, 'update_order_status'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'receipt_page'));
        add_filter('woocommerce_valid_order_statuses_for_cancel', array($this, 'validate_cancelling'), 20, 2);
    }

    /**
     * Generate a Decrypted Text Input HTML.
     *
     * @param string $key Field key.
     * @param array  $data Field data.
     * @since  1.0.0
     * @return string
     */
    public function generate_encrypted_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args($data, $defaults);

        ob_start();
?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok. 
                                                                                                                ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
                    <input class="input-text regular-input <?php echo esc_attr($data['class']); ?>" type="<?php echo esc_attr($data['type']); ?>" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" value="<?php echo esc_attr($this->crypto->decrypt($this->get_option($key))); ?>" placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok. 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ?> />
                    <?php echo $this->get_description_html($data); // WPCS: XSS ok. 
                    ?>
                </fieldset>
            </td>
        </tr>
<?php

        return ob_get_clean();
    }

    public function process_admin_options()
    {
        $post_data = $this->get_post_data();

        foreach ($this->get_form_fields() as $key => $field) {
            if ('title' !== $this->get_field_type($field)) {
                try {
                    if (in_array($key, ['merchant_id', 'merchant_key', 'merchant_secret'])) {
                        if ($key === 'merchant_id' && $this->check_merchant_conflict($this->get_field_value($key, $field, $post_data))) {
                            //do nothing
                            global $triedToSaveSameMerchant;
                            $triedToSaveSameMerchant = true;
                        } else {
                            if ('yes' === $this->debug) $this->log->add($this->id, "process_admin_options()-->" . $key);
                            $this->settings[$key] = $this->crypto->encrypt($this->get_field_value($key, $field, $post_data));
                            if ('yes' === $this->debug) $this->log->add($this->id, $this->settings[$key]);
                        }
                    } else {
                        $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
                    }
                } catch (Exception $e) {
                    $this->add_error($e->getMessage());
                }
            }
        }

        return update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings), 'yes');
    }

    /**
     * Returns a bool that indicates if currency is amongst the supported ones.
     * 
     * @param array $statuses Allowed statuses an order can have to be eligible for cancelling.
     * @return array
     */
    public function validate_cancelling($statuses, $order = '')
    {
        //$order->has_status()
        if ('yes' === $this->debug) $this->log->add($this->id, "order_can_cancel()");
        // array_push($statuses, 'processing');
        // $this->log->add($this->id, print_r($statuses, true));
        // return $statuses;
        return array('pending', 'processing', 'on-hold', 'failed');
    }

    /**
     * Returns a bool that indicates if currency is amongst the supported ones.
     *
     * @return bool
     */
    public function using_supported_currency()
    {
        $currency = get_woocommerce_currency();

        if (in_array($currency, array('BRL', 'EUR', 'USD', 'GBP'))) {
            return true;
        }
        return false;
    }

    /**
     * Get merchant id.
     *
     * @return string
     */
    public function get_id()
    {
        return (null !== $this->get_option('merchant_id') && $this->get_option('merchant_id') !== "") ? $this->crypto->decrypt($this->get_option('merchant_id')) : '';
    }

    /**
     * Get merchant key.
     *
     * @return string
     */
    public function get_key()
    {
        return (null !== $this->get_option('merchant_key') && $this->get_option('merchant_key') !== "") ? $this->crypto->decrypt($this->get_option('merchant_key')) : '';
    }

    /**
     * Get merchant secret.
     *
     * @return string
     */
    public function get_secret()
    {
        return (null !== $this->get_option('merchant_secret') && $this->get_option('merchant_secret') !== "") ? $this->crypto->decrypt($this->get_option('merchant_secret')) : '';
    }

    /**
     * Get merchant logo.
     *
     * @return string
     */
    public function get_logo()
    {
        return (null !== $this->get_option('merchant_logo') && $this->get_option('merchant_logo') !== "") ? $this->get_option('merchant_logo') : '';
    }

    /**
     * check merchant conflict with donations.
     *
     * @return string
     */
    public function check_merchant_conflict($id)
    {
        $donation_options = get_option('p4f_options');
        $donation_merchant_id = ($donation_options !== false && !empty($donation_options['merchant_id']) && null !== $donation_options['merchant_id'] && $donation_options['merchant_id'] !== "") ? $this->crypto->decrypt($donation_options['merchant_id']) : '';
        //$wc_merchant_id = (null !== $this->get_option('merchant_id') && $this->get_option('merchant_id') !== "") ? $this->crypto->decrypt($this->get_option('merchant_id')) : '';
        $wc_merchant_id = $id;

        if ($donation_merchant_id === '' && $wc_merchant_id === '') return false;
        return ($donation_merchant_id === $wc_merchant_id);
    }


    /**
     * Returns a value indicating the the Gateway is available or not. It's called
     * automatically by WooCommerce before allowing customers to use the gateway
     * for payment.
     *
     * @return bool
     */
    public function is_available()
    {
        // Test if is valid for use.
        return 'yes' === $this->get_option('enabled') && '' !== $this->get_id() && '' !== $this->get_key() && '' !== $this->get_secret() && $this->using_supported_currency() && !$this->check_merchant_conflict($this->get_id());
    }

    /**
     * Get log.
     *
     * @return string
     */
    protected function get_log_view()
    {
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.2', '>=')) {
            return '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.log')) . '">' . __('System Status &gt; Logs', 'woocommerce-pay4fun') . '</a>';
        }

        return '<code>woocommerce/logs/' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.txt</code>';
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'         => array(
                'title'       => __('Enable/Disable', 'woocommerce-pay4fun'),
                'type'        => 'checkbox',
                'label'       => __('Enable Pay4Fun', 'woocommerce-pay4fun'),
                'default'     => 'yes',
            ),
            'title'           => array(
                'title'       => __('Title', 'woocommerce-pay4fun'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-pay4fun'),
                'desc_tip'    => true,
                'default'     => __('Pay4Fun', 'woocommerce-pay4fun'),
            ),
            'description'     => array(
                'title'       => __('Description', 'woocommerce-pay4fun'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-pay4fun'),
                'default'     => __('Pay via pay4fun', 'woocommerce-pay4fun'),
            ),
            'merchant_id'     => array(
                'title'       => __('Merchant ID', 'woocommerce-pay4fun'),
                'type'        => 'encrypted',
                'description' => __('You Merchant ID Registered on Pay4Fun', 'woocommerce-pay4fun'),
                'desc_tip'    => true,
                'default'     => '',
            ),
            'merchant_secret' => array(
                'title'       => __('Merchant Secret', 'woocommerce-pay4fun'),
                'type'        => 'encrypted',
                'description' => __('You Merchant Secret Registered on Pay4Fun', 'woocommerce-pay4fun'),
                'desc_tip'    => true,
                'default'     => '',
            ),
            'merchant_key'    => array(
                'title'       => __('Merchant Key', 'woocommerce-pay4fun'),
                'type'        => 'encrypted',
                'description' => __('You Merchant Key Registered on Pay4Fun', 'woocommerce-pay4fun'),
                'desc_tip'    => true,
                'default'     => '',
            ),
            'merchant_logo'   => array(
                'title'       => __('Merchant Logo', 'woocommerce-pay4fun'),
                'type'        => 'text',
                'description' => __('Add your Logo into Pay4Fun checkout page.', 'woocommerce-pay4fun'),
                'desc_tip'    => true,
                'default'     => '',
            ),
            'order_prefix'    => array(
                'title'       => __('WC Order Prefix', 'woocommerce-pay4fun'),
                'type'        => 'text',
                'description' => __('Use a prefix to uniquely identify your order with Pay4fun if using multiple sites.', 'woocommerce-pay4fun'),
                'desc_tip'    => true,
                'default'     => __('WC_', 'woocommerce-pay4fun'),
            ),
            'language'     => array(
                'title'       => __('Language', 'woocommerce-pay4fun'),
                'description' => __('Select the language to be used in the pay4fun checkout page.', 'woocommerce-pay4fun'),
                'desc_tip'    => true,
                'type'        => 'select',
                'default'     => 'pt-BR',
                'options'     => array(
                    'pt-BR'     => 'pt-BR',
                    'en-US'     => 'en-US',
                    'es-ES'     => 'es-ES'
                )
            ),
            'complete_order'  => array(
                'title'       => __('Complete Order on Payment?', 'woocommerce-pay4fun'),
                'type'        => 'checkbox',
                'label'       => __('Change order status to "Completed" once pay4fun transfer has been confirmed, otherwise it will change order status to "Processing" (Only applicable for virtual/downloadable products)', 'woocommerce-pay4fun'),
                'default'     => 'yes',
            ),
            'sandbox'         => array(
                'title'       => __('Sandbox Mode?', 'woocommerce-pay4fun'),
                'type'        => 'checkbox',
                'label'       => __('Run your payments in sandbox mode for testing purposes only.', 'woocommerce-pay4fun'),
                'default'     => 'no',
            ),
            'debug'           => array(
                'title'       => __('Debug Log', 'woocommerce-pay4fun'),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', 'woocommerce-pay4fun'),
                'default'     => 'no',
                /* translators: %s: log page link */
                'description' => sprintf(__('Log pay4fun events, such as API requests, inside %s', 'woocommerce-pay4fun'), $this->get_log_view()),
            ),
        );
    }

    /**
     * Admin page.
     */
    public function admin_options()
    {
        include P4F_PLUGIN_DIR . '/views/html-admin-page.php';

        $wc_merchant_id = $this->get_id();
        $donate_settings = get_option('p4f_options');
        $donation_merchant_id = (!empty($donate_settings['merchant_id']) && null !== $donate_settings['merchant_id'] && $donate_settings['merchant_id'] !== "") ? $this->crypto->decrypt($donate_settings['merchant_id']) : '';
        wp_enqueue_script('p4f_wc_adminpage_validation_script', plugin_dir_url(__FILE__) . '../assets/js/p4f-wc-page-field-validation.js', array('jquery'));

        wp_localize_script(
            'p4f_wc_adminpage_validation_script',
            'merchant',
            array(
                'wc_merchant_id' => $wc_merchant_id,
                'donation_merchant_id' => $donation_merchant_id,
                'id_conflict_message' => __('WooCommerce Merchant cannot be the same as the Donation Merchant.', 'woocommerce-pay4fun')
            )
        );
    }

    /**
     * Send email notification.
     *
     * @param string $subject Email subject.
     * @param string $title   Email title.
     * @param string $message Email message.
     */
    protected function send_email($subject, $title, $message)
    {
        $mailer = WC()->mailer();

        $mailer->send(get_option('admin_email'), $subject, $mailer->wrap_message($title, $message));
    }


    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        if ('yes' === $this->debug) $this->log->add($this->id, "process_payment() --> " .  WC()->api_request_url('WC_Pay4Fun_Gateway'));
        $order = wc_get_order($order_id);
        $orderItems = $order->get_items();
        $firstItem = reset($orderItems);

        //$this->log->add($this->id, print_r($firstItem->get_name(), true));
        $mode = ($this->sandbox === "yes") ? Pay4Fun::MODE_MERCHANT_SANDBOX : Pay4Fun::MODE_MERCHANT_LIVE;
        $debug = ($this->debug === "yes");


        if ('yes' === $this->debug) $this->log->add($this->id, "MODE: " . $mode . " | debug: " . $debug);
        $p4f = new Pay4Fun($mode, $debug);
        $p4f->setCredentials(
            $this->merchant_id,
            $this->merchant_secret,
            $this->merchant_key
        );

        $confirmationURL = filter_var(get_site_url() . "/wp-json/p4f-plugin/v1/wc-confirmation/?returnURL=" . WC()->api_request_url('WC_Pay4Fun_Gateway') . "&order_number=" . $order->get_order_number(), FILTER_SANITIZE_URL);
        $request = [
            'amount' => $this->get_order_total(),
            'merchantInvoiceId' => $this->order_prefix . $order->get_order_number(),
            'language' => $this->language,
            'currency' => get_woocommerce_currency(), //$this->options['p4f_currency'],
            'okUrl' => $this->get_return_url($order),
            'notOkUrl' => html_entity_decode($order->get_cancel_order_url()),
            'confirmationUrl' => $confirmationURL,
            //'merchantLogo' => $this->merchant_logo,
            //'description' => $firstItem->get_name()
            'description' => substr($firstItem->get_name(), 0, 40) //name of the first product in the order
        ];
        if (!empty($this->merchant_logo)) $request['merchantLogo'] = $this->merchant_logo;

        if ('yes' === $this->debug) $this->log->add($this->id, "REQUEST FOR PAY4FUN:");
        if ('yes' === $this->debug) $this->log->add($this->id, print_r($request, true));

        $result = $p4f->payIn($request);
        if ('yes' === $this->debug) $this->log->add($this->id, "RESPONSE FROM PAY4FUN:");
        if ('yes' === $this->debug) $this->log->add($this->id, print_r($result, true));
        if (is_array($result) && isset($result['success']) && $result['success'] === true) {
            // Remove cart.
            //WC()->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $result['url'],
            );
        } else {
            //wc_add_notice($result['message'], 'error');
            wc_add_notice(__('We are sorry, there was an error while processing your order with this payment method. Please try again, or if possible use another method. Contact our support if you need assistance.', 'woocommerce-pay4fun'), 'error');
            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }
    }

    /**
     * Output for the order received page.
     *
     * @param int $order_id Order ID.
     */
    public function receipt_page($order_id)
    {
        if ('yes' === $this->debug) $this->log->add($this->id, "receipt_page()");
        $order = wc_get_order($order_id);
        include P4F_PLUGIN_DIR . '/views/html-receipt-page-error.php';
    }

    /**
     * IPN handler.
     */
    public function ipn_handler()
    {
        @ob_clean();

        if ('yes' === $this->debug) $this->log->add($this->id, "ipn_handler()");
        if ('yes' === $this->debug) $this->log->add($this->id, print_r($_POST, true));

        if (!empty($_POST)) {
            $ipn_response['Amount'] = (isset($_POST['Amount']) ? sanitize_text_field($_POST['Amount']) : '');
            $ipn_response['MerchantInvoiceId'] = (isset($_POST['MerchantInvoiceId']) ? sanitize_text_field($_POST['MerchantInvoiceId']) : '');
            $ipn_response['Status'] = (isset($_POST['Status']) ? sanitize_text_field($_POST['Status']) : '');
            $ipn_response['Sign'] = (isset($_POST['Sign']) ? sanitize_text_field($_POST['Sign']) : '');
        }

        if (!empty($ipn_response) && $this->check_fingerprint($ipn_response)) {
            $posted = wp_unslash($_POST);
            http_response_code(200);
            $response = json_encode(
                array(
                    'status' => 'success',
                    'message' => 'Pay4Fun IPN Request Detected, Validated and Processed.'
                )
            );
            do_action('valid_pay4fun_ipn_request', $posted);
            exit;
        }
        if ('yes' === $this->debug) $this->log->add($this->id, "Pay4Fun IPN Request Failure");
        //wp_die('Pay4Fun IPN Request Failure', 'Pay4Fun IPN', array('response' => 500));
        $response = json_encode(
            array(
                'status' => 'failure',
                'message' => 'Invalid Pay4Fun IPN Request. Unable to validate fingerprint.'
            )
        );
        header('Content-Type: application/json');
        http_response_code(500);
        echo $response;
        die();
    }

    /**
     * Validates the IPN fingerprint to confirm message autenticity
     *
     * @param array $posted pay4fun post data.
     */
    private function check_fingerprint($data)
    {
        $merchant_key = $this->merchant_key;
        $merchant_id = $this->merchant_id;
        $amount = number_format($data['Amount'], 2, '', '');
        $invoice_id = $data['MerchantInvoiceId'];
        $response_code = $data['Status'];


        $message =  $merchant_id . $amount . $invoice_id . $response_code;
        $hash = hash_hmac('sha256', utf8_encode(strtoupper($message)), utf8_encode($merchant_key));

        if ('yes' === $this->debug) $this->log->add($this->id, print_r($message, true));
        if ('yes' === $this->debug) $this->log->add($this->id, print_r(strtoupper($hash), true));
        if ('yes' === $this->debug) $this->log->add($this->id, print_r($data['Sign'], true));

        if (strtoupper($hash) === strtoupper($data['Sign'])) return true;
        return false;
    }

    /**
     * Update order status.
     *
     * @param array $posted pay4fun post data.
     */
    public function update_order_status($posted)
    {
        if ('yes' === $this->debug) $this->log->add($this->id, "update_order_status()");
        if ('yes' === $this->debug) $this->log->add($this->id, print_r($posted, true));
        if (isset($posted['MerchantInvoiceId'])) {
            $id    = (int)  substr($posted['MerchantInvoiceId'], strlen($this->order_prefix), strlen($posted['MerchantInvoiceId']));
            $order = wc_get_order($id);

            // Check if order exists.
            if (!$order) {
                return;
            }

            $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

            // Checks whether the invoice number matches the order.
            // If true processes the payment.
            if ($order_id === $id) {
                if ('yes' === $this->debug) {
                    $this->log->add($this->id, 'pay4fun payment status for order ' . $order->get_order_number() . ' is: ' . intval($posted['Status']));
                }

                // Save meta data.
                $this->save_payment_meta_data($order, $posted);

                switch (intval($posted['Status'])) {
                    case 201:

                        if ($this->complete_order !== 'yes') {
                            $order->update_status('processing', __("pay4fun: Payment approved (#{$posted['TransactionId']}).", 'woocommerce-pay4fun'));
                            wc_reduce_stock_levels($order_id);
                        } else {
                            $order->add_order_note(__("pay4fun: Payment approved (#{$posted['TransactionId']}).", 'woocommerce-pay4fun'));
                            $order->payment_complete(sanitize_text_field((string) $posted['TransactionId']));
                        }

                        break;
                    case 2:
                        $order->update_status('on-hold', __('pay4fun: Payment under review.', 'woocommerce-pay4fun'));

                        // Reduce stock for billets.
                        if (function_exists('wc_reduce_stock_levels')) {
                            wc_reduce_stock_levels($order_id);
                        }

                        break;
                    case 3:
                        // Sometimes pay4fun should change an order from cancelled to paid, so we need to handle it.
                        if (method_exists($order, 'get_status') && 'cancelled' === $order->get_status()) {
                            $order->update_status('processing', __('pay4fun: Payment approved.', 'woocommerce-pay4fun'));
                            wc_reduce_stock_levels($order_id);
                        } else {
                            $order->add_order_note(__('pay4fun: Payment approved.', 'woocommerce-pay4fun'));

                            // Changing the order for processing and reduces the stock.
                            $order->payment_complete(sanitize_text_field((string) $posted['TransactionId']));
                        }

                        break;
                    case 4:
                        $order->add_order_note(__('pay4fun: Payment completed and credited to your account.', 'woocommerce-pay4fun'));

                        break;
                    case 5:
                        $order->update_status('on-hold', __('pay4fun: Payment came into dispute.', 'woocommerce-pay4fun'));
                        $this->send_email(
                            /* translators: %s: order number */
                            sprintf(__('Payment for order %s came into dispute', 'woocommerce-pay4fun'), $order->get_order_number()),
                            __('Payment in dispute', 'woocommerce-pay4fun'),
                            /* translators: %s: order number */
                            sprintf(__('Order %s has been marked as on-hold, because the payment came into dispute in pay4fun.', 'woocommerce-pay4fun'), $order->get_order_number())
                        );

                        break;
                    case 6:
                        $order->update_status('refunded', __('pay4fun: Payment refunded.', 'woocommerce-pay4fun'));
                        $this->send_email(
                            /* translators: %s: order number */
                            sprintf(__('Payment for order %s refunded', 'woocommerce-pay4fun'), $order->get_order_number()),
                            __('Payment refunded', 'woocommerce-pay4fun'),
                            /* translators: %s: order number */
                            sprintf(__('Order %s has been marked as refunded by pay4fun.', 'woocommerce-pay4fun'), $order->get_order_number())
                        );

                        if (function_exists('wc_increase_stock_levels')) {
                            wc_increase_stock_levels($order_id);
                        }

                        break;
                    case 7:
                        $order->update_status('cancelled', __('pay4fun: Payment canceled.', 'woocommerce-pay4fun'));

                        if (function_exists('wc_increase_stock_levels')) {
                            wc_increase_stock_levels($order_id);
                        }

                        break;

                    default:
                        break;
                }
            } else {
                if ('yes' === $this->debug) {
                    $this->log->add($this->id, 'Error: Order Key does not match with pay4fun reference.');
                }
            }
        }
    }

    /**
     * Save payment meta data.
     *
     * @param WC_Order $order Order instance.
     * @param object    $posted Posted data.
     */
    protected function save_payment_meta_data($order, $posted)
    {
        $meta_data    = array();
        $payment_data = array(
            'type'         => '',
            'method'       => '',
            'installments' => '',
            'link'         => '',
        );

        if (isset($posted['CustomerEmail'])) {
            $meta_data[__('Payer email', 'woocommerce-pay4fun')] = sanitize_text_field((string) $posted['CustomerEmail']);
        }
        if (isset($posted->sender->name)) {
            $meta_data[__('Payer name', 'woocommerce-pay4fun')] = sanitize_text_field((string) $posted->sender->name);
        }
        if (isset($posted->paymentMethod->type)) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
            $payment_data['type'] = intval($posted->paymentMethod->type); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

            $meta_data[__('Payment type', 'woocommerce-pay4fun')] = $this->api->get_payment_name_by_type($payment_data['type']);
        }
        if (isset($posted->paymentMethod->code)) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
            $payment_data['method'] = $this->api->get_payment_method_name(intval($posted->paymentMethod->code)); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

            $meta_data[__('Payment method', 'woocommerce-pay4fun')] = $payment_data['method'];
        }
        if (isset($posted->installmentCount)) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
            $payment_data['installments'] = sanitize_text_field((string) $posted->installmentCount); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

            $meta_data[__('Installments', 'woocommerce-pay4fun')] = $payment_data['installments'];
        }
        if (isset($posted->paymentLink)) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
            $payment_data['link'] = sanitize_text_field((string) $posted->paymentLink); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

            $meta_data[__('Payment URL', 'woocommerce-pay4fun')] = $payment_data['link'];
        }
        if (isset($posted->creditorFees->intermediationRateAmount)) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
            $meta_data[__('Intermediation Rate', 'woocommerce-pay4fun')] = sanitize_text_field((string) $posted->creditorFees->intermediationRateAmount); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
        }
        if (isset($posted->creditorFees->intermediationFeeAmount)) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
            $meta_data[__('Intermediation Fee', 'woocommerce-pay4fun')] = sanitize_text_field((string) $posted->creditorFees->intermediationFeeAmount); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
        }

        $meta_data['_WC_Pay4Fun_payment_data'] = $payment_data;

        // WooCommerce 3.0 or later.
        if (method_exists($order, 'update_meta_data')) {
            foreach ($meta_data as $key => $value) {
                $order->update_meta_data($key, $value);
            }
            $order->save();
        } else {
            foreach ($meta_data as $key => $value) {
                update_post_meta($order->id, $key, $value);
            }
        }
    }
}
