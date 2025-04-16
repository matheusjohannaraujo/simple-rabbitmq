# Simple RabbitMQ

**Simple RabbitMQ** is a PHP class that offers a clean and reusable abstraction for working with RabbitMQ. It simplifies the process of connecting to message queues and performing common operations like publishing messages, consuming queues, acknowledging deliveries, and managing exchanges and bindings — all without having to deal with the complexity of the underlying configuration.

## 📦 Installation

You can install the library via [Packagist/Composer](https://packagist.org/packages/mjohann/simple-rabbitmq):

```bash
composer require mjohann/simple-rabbitmq
```

## ⚙️ Requirements

- PHP 7.4 or higher

## 🚀 Features

- Simple RabbitMQ uses [`enqueue/amqp-lib`](https://packagist.org/packages/enqueue/amqp-lib) as a dependency
- Supported:
    - config — Configures the connection parameters (e.g., host, port, credentials).
    - open — Opens a connection to the RabbitMQ server.
    - close — Closes the connection.
    - exchange($name) — Declares an exchange.
    - queue($name) — Declares a queue.
    - queueBind() — Binds a queue to an exchange.
    - pub_exchange($message) — Publishes a message to the exchange.
    - sub($callback) — Subscribes a callback function to consume messages from the queue.
    - waitCallbacks($milliseconds) — Waits for messages and dispatches them to the subscribed callbacks for a given duration.
    - readMessage() — (Commented out, but exists) Likely reads a single message from the queue.
    - acknowledge($message) — Acknowledges receipt of a message (seen in the consumer callback via $consumer->acknowledge()).

## 🧪 Usage Example

### Publisher
```php
<?php

require_once "vendor/autoload.php";

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

// Publish 30,000 messages to the exchange
for ($i = 1; $i <= 30000; $i++) {
    $srmq->pub_exchange("test " . $i); // Publish a message with a test string
    echo $i, PHP_EOL; // Output the message number to the console
}

// Close the connection to the RabbitMQ server
$srmq->close();

```

### Subscriber
```php
<?php

require_once "vendor/autoload.php";

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
$callback = function ($message, $consumer) {
    echo "Message: ", $message->getBody(), PHP_EOL; // Print the message content
    $consumer->acknowledge($message); // Acknowledge the message to RabbitMQ
    return true; // Return true to continue listening
};

// Subscribe the first callback to the queue
$srmq->sub($callback);

// Optionally, read a single message manually (commented out)
// $srmq->readMessage();

// Wait for messages and trigger callbacks for up to 15 seconds
$srmq->waitCallbacks(15000); // 15000 milliseconds = 15 seconds

// Close the connection to the RabbitMQ server
$srmq->close();
```

For more examples, see the [`example/`](example/) file in the repository.

## 📁 Project Structure

```
simple-rabbitmq/
├── src/
│   └── SimpleRabbitMQ.php
├── example/
│   └── docker-compose.php
│   └── sub.php
│   └── pub.php
├── composer.json
├── .gitignore
├── LICENSE
└── README.md
```

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more information.

## 👨‍💻 Author

Developed by [Matheus Johann Araújo](https://github.com/matheusjohannaraujo) – Pernambuco, Brazil.
