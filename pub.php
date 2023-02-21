<?php

require_once "vendor/autoload.php";

$rmq = new \Lib\SimpleRabbitMQ();
$rmq->open();
$rmq->openChannel();
$rmq->exchange("my_exchange");
$rmq->queue("my_queue");
$rmq->queueBind();
for ($i = 1; $i <= 1000; $i++) { 
    $rmq->pub("test " . $i);
}
$rmq->closeChannel();
$rmq->close();
