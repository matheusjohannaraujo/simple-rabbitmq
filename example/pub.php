<?php

require_once "../vendor/autoload.php";

use MJohann\Packlib\SimpleRabbitMQ;

// Create a new instance of the SimpleRabbitMQ class
$srmq = new SimpleRabbitMQ();

// Configure connection parameters
$srmq->config();

// Open the connection to the RabbitMQ server
$srmq->open();

// Declare an exchange named "my_exchange"
$srmq->exchange("my_exchange");

// Declare a queue named "my_queue"
$srmq->queue("my_queue");

// Bind the declared queue to the exchange
$srmq->queueBind();

// Publish 20,000 messages to the exchange
for ($i = 1; $i <= 20000; $i++) {
    $srmq->pub_exchange("test " . $i); // Publish a message with a test string
    echo $i, PHP_EOL; // Output the message number to the console
}

// Close the connection to the RabbitMQ server
$srmq->close();
