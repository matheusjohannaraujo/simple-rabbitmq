<?php

use MJohann\Packlib\Facades\SimpleRabbitMQ;

require_once "../vendor/autoload.php";

// Using a Facade to instantiate and configure an instance of the SimpleRabbitMQ class.
SimpleRabbitMQ::init();

// Declare an exchange named "my_exchange"
SimpleRabbitMQ::exchange("my_exchange");

// Declare a queue named "my_queue"
SimpleRabbitMQ::queue("my_queue");

// Bind the declared queue to the exchange
SimpleRabbitMQ::queueBind();

// Define the first callback function to handle incoming messages
$callback1 = function ($message, $consumer) {
    echo "Message 1: ", $message->getBody(), PHP_EOL; // Print the message content
    $consumer->acknowledge($message); // Acknowledge the message to RabbitMQ
    return true; // Return true to continue listening
};

// Subscribe the first callback to the queue
SimpleRabbitMQ::sub($callback1);

// Define the second callback function to handle incoming messages
$callback2 = function ($message, $consumer) {
    echo "Message 2: ", $message->getBody(), PHP_EOL; // Print the message content
    $consumer->acknowledge($message); // Acknowledge the message to RabbitMQ
    return true; // Return true to continue listening
};

// Subscribe the second callback to the queue
SimpleRabbitMQ::sub($callback2);

// Optionally, read a single message manually (commented out)
// $srmq->readMessage();

// Wait for messages and trigger callbacks for up to 15 seconds
//$srmq->waitCallbacks(15000); // 15000 milliseconds = 15 seconds

SimpleRabbitMQ::waitCallbacks(); // Infinite wait

// Close the connection to the RabbitMQ server
SimpleRabbitMQ::close();
