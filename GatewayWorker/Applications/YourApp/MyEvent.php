<?php
use \GatewayWorker\Lib\Gateway;

// MyEvent类
class MyEvent
{
    public static function onConnect(){
        echo 'myEvent--connetct';
    }
    public static function onMessage($client_id, $message)
    {
        // Gateway::sendToCurrentClient('works');
    }
}