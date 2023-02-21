<?php

require_once "vendor/autoload.php";

$rmq = new \Lib\SimpleRabbitMQ();
$rmq->open();
$rmq->openChannel();
$rmq->queue("my_queue");

$cb = function($msg)
{
    echo "Message: ", $msg->body, PHP_EOL;
    return true;
};

$rmq->sub($cb);
//$rmq->readMessage();
//$rmq->readAllMessages();
$rmq->waitCallbacks(100);
$rmq->closeChannel();
$rmq->close();
