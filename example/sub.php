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

// Define the first callback function to handle incoming messages
$callback1 = function ($message, $consumer) {
    echo "Message 1: ", $message->getBody(), PHP_EOL; // Print the message content
    $consumer->acknowledge($message); // Acknowledge the message to RabbitMQ
    return true; // Return true to continue listening
};

// Subscribe the first callback to the queue
$srmq->sub($callback1);

// Define the second callback function to handle incoming messages
$callback2 = function ($message, $consumer) {
    echo "Message 2: ", $message->getBody(), PHP_EOL; // Print the message content
    $consumer->acknowledge($message); // Acknowledge the message to RabbitMQ
    return true; // Return true to continue listening
};

// Subscribe the second callback to the queue
$srmq->sub($callback2);

// Optionally, read a single message manually (commented out)
// $srmq->readMessage();

// Wait for messages and trigger callbacks for up to 15 seconds
$srmq->waitCallbacks(15000); // 15000 milliseconds = 15 seconds

// Close the connection to the RabbitMQ server
$srmq->close();
