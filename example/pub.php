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

// Publish 10,000 messages to the exchange
for ($i = 1; $i <= 10000; $i++) {
    SimpleRabbitMQ::pub_exchange("test " . $i); // Publish a message with a test string
    echo $i, PHP_EOL; // Output the message number to the console
}

// Close the connection to the RabbitMQ server
SimpleRabbitMQ::close();
