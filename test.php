<?php

require_once 'SmsSender.php';

$sender = new SmsSender('<apiKey>');

try {
    $sender->sendSMS(array(
        'phone' => '<receiver>', 
        'sender' => '<sender name>',
        'text' => '<message>'
    ));
    echo 'ok';
} catch (Exception $e) {
    echo $e;
}

?>
