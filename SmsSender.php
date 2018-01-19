<?php


class SmsStatus {
    const NOT_SENT = 101;
    const OK_SENT = 102;
    const OK_DELIVERED = 201;
    const ERR_SRC_INVALID = 301;
    const ERR_DEST_INVALID = 302;
    const ERR_SKIP_LETTER = 303;
    const ERR_NOT_ALLOWED = 304;
    const ERR_DELIVERY_FAILED = 305;
    const ERR_LOST = 306;
    const ERR_INTERNAL = 307;
    const UNKNOWN = 401; // например, если появился новый код от unisender

    static public function getName ($status) {
        $statusesClass = new ReflectionClass('SmsStatus');
        $statusesArray = $statusesClass->getConstants();
	$inverseArray = array_flip($statusesArray);
        return $inverseArray[$status];
    }
}

class SmsSender {
    const UNISENDER_API_BASE_URL = 'https://api.unisender.com/ru/api/';

    private $apiKey;
    protected $logger;

    public function __construct ($apiKey, $logger = null) {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    public function checkSms ($params) {
        $smsId = $params['sms_id'];

        $jsonResponse = $this->sendGetRequest('checkSms', array(
            'sms_id' => $smsId
        ));

        $statusString = $jsonResponse->result->status;
        $statusesClass = new ReflectionClass('SmsStatus');
        $statusesArray = $statusesClass->getConstants();
        $code = $statusesArray[strtoupper($statusString)];
        return $code ? $code : SmsStatus::UNKNOWN;
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

            $jsonResponse = $this->sendGetRequest('sendSms', array(
                'phone' => $phone,
                'sender' => $sender,
                'text' => $text
            ));

            return $jsonResponse->result->sms_id;
        }
    }

    private function buildRequestURI ($method, $params) {
        $params = array_merge($params, array(
            'format' => 'json',
            'api_key' => $this->apiKey
        ));
        $query = http_build_query($params);
        return self::UNISENDER_API_BASE_URL . $method . '?' . $query;
    }

    private function checkResponseError ($jsonResponse, $requestURI) {
        if (isset($jsonResponse->error)) {
            $errMsg = 'Unisender API returned an error: ' . $jsonResponse->error . ', code: ' . $jsonResponse->code;
            $this->log('error', $errMsg);
            $this->log('error', 'Request uri: ' . $requestURI);

            throw new Exception($errMsg);
        }
    }

    private function log ($level, $message) {
        if ($this->logger) {
            $this->logger->log($level, 'Request uri: ' . $requestURI);
        }
    }

    private function logRequestWarnings ($jsonResponse, $requestURI) {
        if (isset($jsonResponse->warnings)) {
            $this->log('warning', 'Warnings while sending the sms: ');
            for ($i = 0; $i < count($jsonResponse->warnings); $i++) {
                $this->log('warning', $i . ': ' . $jsonResponse->warnings[$i]);
            }
            $this->log('warning', 'Request uri: ' . $requestURI);
        }
    }

    private function parseJson ($response, $requestURI) {
        $jsonResponse = json_decode($response);
        
        if (!$jsonResponse) {
            $this->log('error', 'Invalid JSON returned from unisender API');
            $this->log('error', 'Request uri: ' . $requestURI);
            throw new Exception('Invalid JSON returned from unisender API: ' . $response);
        }

        return $jsonResponse;
    }

    private function sendGetRequest ($method, $params) {
        $requestURI = $this->buildRequestURI($method, $params);
        if (!$curl = curl_init()) {
            throw new Exception('Failed to initialize curl (curl_init returned invalid value)');
        }
            
        curl_setopt($curl, CURLOPT_URL, $requestURI);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        if (!$response) {
            $this->log('error', 'An error occurred while trying to send the sms: failed to get response from sms-sending server.');
            $this->log('error', 'Request uri: ' . $requestURI);
            throw new Exception('Failed to get response from sms-sending server. Curl error: ' . curl_error($curl));
        }

        $jsonResponse = $this->parseJson($response, $requestURI);

        $this->checkResponseError($jsonResponse, $requestURI);
        $this->logRequestWarnings($jsonResponse, $requestURI);

        return $jsonResponse;
    }
}
