<?php

require_once "vendor/autoload.php";

$rmq = new \Lib\SimpleRabbitMQ();
$rmq->open();
$rmq->openChannel();
$rmq->exchange("my_exchange");
$rmq->queue("my_queue");
$rmq->queueBind();
$callback = function($msg) {
    echo "Message: ", $msg->body, PHP_EOL;
    return true;// ACK
};
$rmq->sub($callback);
//$rmq->readMessage();
//$rmq->readAllMessages();
$rmq->waitCallbacks(1);
$rmq->closeChannel();
$rmq->close();
