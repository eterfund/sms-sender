<?php

define('UNISENDER_API_BASE_URL', 'https://api.unisender.com/ru/api/sendSms');

class SmsSender {
    public $config;
    protected $logger;

    public function __construct ($config, $logger = null) {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function sendSMS ($to, $msg) {
        if ($to) {
            $phone = preg_replace("#[^\d]#", "", $to);
            if (9000000000 <= $phone and $phone <= 9999999999) {
                $phone = $phone + 70000000000;
            }
            if (89000000000 <= $phone and $phone <= 89999999999) {
                $phone = $phone - 10000000000;
            }

            $requestURI = $this->buildRequestURI($phone, $msg);

            if ($curl = curl_init()) {
                curl_setopt($curl, CURLOPT_URL, $requestURI);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);
            } else {
                throw new Exception('Failed to initialize curl (curl_init returned invalid value)');
            }

            if (!$response) {
                $this->log('error', 'An error occurred while trying to send the sms: failed to get response from sms-sending server.');
                $this->log('error', 'Request uri: ' . $requestURI);
                throw new Exception('Failed to get response from sms-sending server');
            }

            $jsonResponse = json_decode($response);
            if (!$jsonResponse) {
                $this->log('error', 'Invalid JSON returned from unisender API');
                $this->log('error', 'Request uri: ' . $requestURI);
                throw new Exception('Invalid JSON returned from unisender API');
            }

            if (isset($jsonResponse->error)) {
                $this->log('error', 'Unisender API returned an error code: ' . $jsonResponse->code);
                $this->log('error', 'Request uri: ' . $requestURI);

                throw new Exception('Unisender API returned an error code: ' . $jsonResponse->code);
            }

            if (isset($jsonResponse->warnings)) {
                $this->log('warning', 'Warnings while sending the sms: ');
                for ($i = 0; $i < count($jsonResponse->warnings); $i++) {
                    $this->log('warning', $i . ': ' . $jsonResponse->warnings[$i]);
                }
                $this->log('warning', 'Request uri: ' . $requestURI);
            }
        }
    }

    protected function buildRequestURI ($phone, $msg) {
        return UNISENDER_API_BASE_URL .
            '?format=json' . 
            '&api_key=' . $this->config['unisender_key'] .
            '&phone=' . $phone .
            '&sender=' . $this->config['sender'] .
            '&text=' . urlencode($msg);
    }

    private function log ($level, $message) {
        if ($this->logger) {
            $this->logger->log($level, 'Request uri: ' . $requestURI);
        }
    }
}