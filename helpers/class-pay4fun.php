<?php

class Pay4Fun
{
    public const MODE_DONATION_LIVE = 1;
    public const MODE_DONATION_SANDBOX = 2;
    public const MODE_MERCHANT_LIVE = 3;
    public const MODE_MERCHANT_SANDBOX = 4;

    private const SANDBOX_URL = 'http://apitest.p4f.com';
    private const LIVE_URL = 'https://api.p4f.com';
    //private const PAY_IN_URI = '/1.0/PayInDirect/Process';
    private const PAY_IN_URI = '/1.0/Ww/Process';
    private $merchant_id;
    private $merchant_secret;
    private $merchant_key;
    private $api_url;
    private $debug;
    private $mode;

    public function __construct(string $mode, bool $debug = true)
    {
        $this->mode = $mode;
        $this->debug = $debug;

        switch ($this->mode) {
            case self::MODE_DONATION_LIVE:
                $this->api_url = self::LIVE_URL;
                break;
            case self::MODE_DONATION_SANDBOX:
                $this->api_url = self::SANDBOX_URL;
                break;
            case self::MODE_MERCHANT_LIVE:
                $this->api_url = self::LIVE_URL;
                break;
            case self::MODE_MERCHANT_SANDBOX:
                $this->api_url = self::SANDBOX_URL;
                break;
        }

        if (!isset($this->api_url) || '' === $this->api_url) {
            throw new Exception(__("API URL not properly set!", 'woocommerce-pay4fun'));
        }
    }

    public function setCredentials(string $id, string $secret, string $key)
    {
        $this->merchant_id = $id;
        $this->merchant_secret = $secret;
        $this->merchant_key = $key;
    }

    public function payIn(array $payload)
    {
        try {
            $request = $this->sanitize($payload);
            //if ($this->debug) print_r($request['amount']);
            $hash = $this->signMessage(number_format($request['amount'], 2, '', '') . $request['merchantInvoiceId']);
            $header = ['merchantId' => $this->merchant_id, 'hash' => $hash];
            $uri = self::PAY_IN_URI;
            $response = $this->post($header, $request, $uri);

            if (null !== $response && is_array($response) && $this->isJson($response['response'])) {
                $result = json_decode($response['response'], true);
                if ('success' == $result['message']) {
                    return [
                        'success' => true,
                        'url' => $result['url'],
                        'message' => '',
                        'data' => $result
                    ];
                }
            }

            if ($this->debug) $this->writeLog(print_r($response, true));
            return [
                'success' => false,
                'url' => '',
                'message' => $response['response']
            ];
        } catch (Throwable $t) {
            if ($this->debug) $this->writeLog(print_r($t, true));
            return [
                'success' => false,
                'url' => '',
                'message' => print_r($t, true)
            ];

            return $t;
        }
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function sanitize(array $payload)
    {
        $sanitized = [];

        if (!isset($payload['amount']) || !is_numeric($payload['amount'])) {
            throw new Exception(__("Amount field is not defined or is not numeric.", 'woocommerce-pay4fun'));
        }
        $sanitized['amount'] = number_format($payload['amount'], 2, '.', '');

        if (!isset($payload['merchantInvoiceId']) || !is_string($payload['merchantInvoiceId']) || empty($payload['merchantInvoiceId'])) {
            throw new Exception(__("merchantInvoiceId field is not defined or is not valid.", 'woocommerce-pay4fun'));
        }
        $sanitized['merchantInvoiceId'] = filter_var($payload['merchantInvoiceId'], FILTER_SANITIZE_STRING);

        if (!isset($payload['currency']) || !is_string($payload['currency']) || strlen($payload['currency']) > 3) {
            throw new Exception(__("Currency field is not defined or is not valid ISO4217 code.", 'woocommerce-pay4fun'));
        }
        $sanitized['currency'] = filter_var($payload['currency'], FILTER_SANITIZE_STRING);

        if (!isset($payload['okUrl']) || !is_string($payload['okUrl']) || !filter_var($payload['okUrl'], FILTER_VALIDATE_URL)) {
            throw new Exception(__("ofUrl field is not defined or is not valid URL.", 'woocommerce-pay4fun'));
        }
        $sanitized['okUrl'] = filter_var($payload['okUrl'], FILTER_SANITIZE_URL);

        if (!isset($payload['notOkUrl']) || !is_string($payload['notOkUrl']) || !filter_var($payload['notOkUrl'], FILTER_VALIDATE_URL)) {
            throw new Exception(__("notOkUrl field is not defined or is not valid URL.", 'woocommerce-pay4fun'));
        }
        $sanitized['notOkUrl'] = filter_var($payload['notOkUrl'], FILTER_SANITIZE_URL);

        if (!isset($payload['confirmationUrl']) || !is_string($payload['confirmationUrl']) || !filter_var($payload['confirmationUrl'], FILTER_VALIDATE_URL)) {
            throw new Exception(__("confirmationUrl field is not defined or is not valid URL.", 'woocommerce-pay4fun'));
        }
        $sanitized['confirmationUrl'] = filter_var($payload['confirmationUrl'], FILTER_SANITIZE_URL);

        //Optional Fields
        if (isset($payload['language'])) {
            if (!is_string($payload['language']) || !in_array($payload['language'], ['en-US', 'pt-BR', 'es-ES'])) {
                throw new Exception(__("Language field is not valid (en-US/pt-BR/es-ES).", 'woocommerce-pay4fun'));
            }
            $sanitized['language'] = filter_var($payload['language'], FILTER_SANITIZE_STRING);
        }

        if (isset($payload['merchantLogo'])) {
            if (!is_string($payload['merchantLogo']) || !filter_var($payload['merchantLogo'], FILTER_VALIDATE_URL)) {
                throw new Exception(__("merchantLogo field is not defined or is not valid URL.", 'woocommerce-pay4fun'));
            }
            $sanitized['merchantLogo'] = filter_var($payload['merchantLogo'], FILTER_SANITIZE_URL);
        }

        if (isset($payload['p4fAccountEmail'])) {
            if (!is_string($payload['p4fAccountEmail']) || !filter_var($payload['p4fAccountEmail'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception(__("p4fAccountEmail field is not a valid e-mail.", 'woocommerce-pay4fun'));
            }
            $sanitized['p4fAccountEmail'] = filter_var($payload['p4fAccountEmail'], FILTER_SANITIZE_EMAIL);
        }

        if (isset($payload['p4fMainId'])) {
            if (!is_string($payload['p4fMainId']) || empty($payload['p4fMainId'])) {
                throw new Exception(__("p4fMainId field is not a valid string.", 'woocommerce-pay4fun'));
            }
            $sanitized['p4fMainId'] = filter_var($payload['p4fMainId'], FILTER_SANITIZE_STRING);
        }

        if (isset($payload['labelId'])) {
            if (!is_numeric($payload['labelId']) || !filter_var($payload['labelId'], FILTER_SANITIZE_NUMBER_FLOAT)) {
                throw new Exception(__("labelId field is not defined or is not numeric.", 'woocommerce-pay4fun'));
            }
            $sanitized['labelId'] = filter_var($payload['labelId'], FILTER_SANITIZE_NUMBER_FLOAT);
        }

        if (isset($payload['description']) && !empty($payload['description'])) {
            if (!is_string($payload['description']) || strlen($payload['description']) > 40) {
                throw new Exception(__("description field is not a valid string or is more than 40 characters.", 'woocommerce-pay4fun'));
            }
            $sanitized['description'] = substr(filter_var($payload['description'], FILTER_SANITIZE_STRING), 0, 40);
        }

        return $sanitized;
    }

    private function post($header = [], $body, $uri)
    {
        $payload = json_encode($body);
        if ($this->debug) $this->writeLog(print_r($payload, true));

        $url = $this->api_url . $uri;
        $header['Content-Type'] = 'application/json; charset=utf-8';


        $response = wp_remote_post($url, array(
            'method'   => 'POST',
            'timeout'  => 45,
            'headers'  => $header,
            'body'     => $payload
        ));
        $response_body = wp_remote_retrieve_body($response);

        if ($this->debug) $this->writeLog(print_r($response, true));
        if ($this->debug) $this->writeLog(wp_remote_retrieve_body($response));
        return [
            'request' => ['header' => $header, 'url' => $url, 'body' => $payload],
            'response' => $response_body,
            'log' => wp_remote_retrieve_response_message($response)
        ];
    }

    private function signMessage($message)
    {
        $data = $this->merchant_id . $message . $this->merchant_secret;
        $hash = hash_hmac('sha256', utf8_encode($data), utf8_encode($this->merchant_key));

        return strtoupper($hash);
    }

    private function writeLog($message)
    {
        $log = fopen(realpath(dirname(__FILE__)) . '/debug.log', 'a');
        fwrite($log, date('D M j G:i:s T Y') . ': ' . $message . "\n");
        fclose($log);
    }
}
