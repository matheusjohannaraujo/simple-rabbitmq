<?php

require_once "vendor/autoload.php";

$rmq = new \Lib\SimpleRabbitMQ();
$rmq->open();
$rmq->openChannel();
$rmq->exchange("my_exchange");
$rmq->queue("my_queue");
$rmq->queueBind();

$callback1 = function($msg) {
    echo "Message 1: ", $msg->body, PHP_EOL;
    return true;// ACK
};
$rmq->sub($callback1);

$callback2 = function($msg) {
    echo "Message 2: ", $msg->body, PHP_EOL;
    return true;// ACK
};
$rmq->sub($callback2);

//$rmq->readMessage();
//$rmq->readAllMessages();
$rmq->waitCallbacks(100);
$rmq->closeChannel();
$rmq->close();
