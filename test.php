<?php

require_once 'SmsSender.php';

$sender = new SmsSender(array(
    'unisender_key' => '<key>',
    'sender' => 'Test'
));

try {
$sender->sendSMS('<number>', 'test');
    echo 'ok';
} catch (Exception $e) {
    echo $e;
}

?>
