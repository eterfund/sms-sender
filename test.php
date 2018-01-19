<?php

require_once 'SmsSender.php';

$sender = new SmsSender('<apiKey>');

try {
    $smsId = $sender->sendSMS(array(
        'phone' => '<receiver>', 
        'sender' => '<sender name>',
        'text' => '<message>'
    ));
    echo 'sent SMS ID ' . $smsId . "\n";
    while (true) {
        sleep(1);
        echo 'Status: ' . $sender->checkSms(array(
            'sms_id' => $smsId
        )) . "\n";
    }
} catch (Exception $e) {
    echo $e;
}

?>
