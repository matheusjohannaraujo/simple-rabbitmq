<?php

require_once "vendor/autoload.php";

$srmq = new \Lib\SimpleRabbitMQ();
$srmq->config();
$srmq->open();
$srmq->openChannel();
$srmq->exchange("my_exchange");
$srmq->queue("my_queue");
$srmq->queueBind();

$callback1 = function($msg) {
    echo "Message 1: ", $msg->body, PHP_EOL;
    return true;// ACK
};
$srmq->sub($callback1);

$callback2 = function($msg) {
    echo "Message 2: ", $msg->body, PHP_EOL;
    return true;// ACK
};
$srmq->sub($callback2);

//$srmq->readMessage();
//$srmq->readAllMessages();
$srmq->waitCallbacks();
$srmq->closeChannel();
$srmq->close();
