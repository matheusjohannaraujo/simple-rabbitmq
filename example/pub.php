<?php

require_once "vendor/autoload.php";

$srmq = new \Lib\SimpleRabbitMQ();
$srmq->config();
$srmq->open();
$srmq->openChannel();
$srmq->exchange("my_exchange");
$srmq->queue("my_queue");
$srmq->queueBind();

for ($i = 1; $i <= 10000; $i++) {
    $srmq->pub("test " . $i);
}

$srmq->closeChannel();
$srmq->close();
