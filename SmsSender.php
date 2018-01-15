<?php

define('UNISENDER_API_BASE_URL', 'https://api.unisender.com/ru/api/sendSms');

class SmsSender {
    private $apiKey;
    protected $logger;

    public function __construct ($apiKey, $logger = null) {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    public function sendSms ($params) {
        $to = $params['phone'];
        $sender = $params['sender'];
        $text = $params['text'];

        if ($to) {
            $phone = preg_replace("#[^\d]#", "", $to);
            if (9000000000 <= $phone and $phone <= 9999999999) {
                $phone = $phone + 70000000000;
            }
            if (89000000000 <= $phone and $phone <= 89999999999) {
                $phone = $phone - 10000000000;
            }

            $requestURI = $this->buildRequestURI($phone, $sender, $text);

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
                throw new Exception('Failed to get response from sms-sending server. Curl error: ' . curl_error($curl));
            }

            $jsonResponse = json_decode($response);
            if (!$jsonResponse) {
                $this->log('error', 'Invalid JSON returned from unisender API');
                $this->log('error', 'Request uri: ' . $requestURI);
                throw new Exception('Invalid JSON returned from unisender API: ' . $response);
            }

            if (isset($jsonResponse->error)) {
                $errMsg = 'Unisender API returned an error: ' . $jsonResponse->error . ', code: ' . $jsonResponse->code;
                $this->log('error', $msg);
                $this->log('error', 'Request uri: ' . $requestURI);

                throw new Exception($errMsg);
            }

            if (isset($jsonResponse->warnings)) {
                $this->log('warning', 'Warnings while sending the sms: ');
                for ($i = 0; $i < count($jsonResponse->warnings); $i++) {
                    $this->log('warning', $i . ': ' . $jsonResponse->warnings[$i]);
                }
                $this->log('warning', 'Request uri: ' . $requestURI);
            }

            return $jsonResponse->result->sms_id;
        }
    }

    protected function buildRequestURI ($phone, $sender, $msg) {
        return UNISENDER_API_BASE_URL .
            '?format=json' . 
            '&api_key=' . $this->apiKey .
            '&phone=' . $phone .
            '&sender=' . $sender .
            '&text=' . urlencode($msg);
    }

    private function log ($level, $message) {
        if ($this->logger) {
            $this->logger->log($level, 'Request uri: ' . $requestURI);
        }
    }
}
