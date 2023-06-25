<?php

require_once "vendor/autoload.php";

$srmq = new \Lib\SimpleRabbitMQ();
$srmq->config();
$srmq->open();
$srmq->exchange("my_exchange");
$srmq->queue("my_queue");
$srmq->queueBind();

for ($i = 1; $i <= 30000; $i++) {
    $srmq->pub_exchange("test " . $i);
    echo $i, PHP_EOL;
}

$srmq->close();
