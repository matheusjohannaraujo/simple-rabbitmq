<?php

/*
	GitHub: https://github.com/matheusjohannaraujo/simple-rabbitmq
	Country: Brasil
	State: Pernambuco
	Developer: Matheus Johann Araujo
	Date: 2025-04-20
*/

namespace MJohann\Packlib;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpLib\AmqpContext;
use Enqueue\AmqpLib\AmqpProducer;
use Enqueue\AmqpLib\AmqpSubscriptionConsumer;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Queue\Message;
use Interop\Queue\Consumer;

class SimpleRabbitMQ
{

    private ?string $host = null;
    private ?int $port = null;
    private ?string $username = null;
    private ?string $password = null;
    private ?bool $persisted = null;
    private ?string $vhost = null;
    private ?AmqpConnectionFactory $connection = null;
    private ?AmqpContext $context = null;
    private ?AmqpTopic $exchange = null;
    private string $exchangeName = "";
    private ?AmqpQueue $queue = null;
    private string $queueName = "";
    private ?AmqpSubscriptionConsumer $subscriptionConsumer = null;

    /**
     * Initializes the RabbitMQ configuration parameters.
     *
     * @param string $host The hostname of the RabbitMQ server.
     * @param int $port The port number of the RabbitMQ server.
     * @param string $username The username for authentication.
     * @param string $password The password for authentication.
     * @param bool $persisted Whether the connection should be persistent.
     * @param string $vhost The virtual host to connect to.
     */
    public function __construct(string $host = "localhost", int $port = 5672, $username = "user", $password = "password", bool $persisted = true, string $vhost = "/")
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->persisted = $persisted;
        $this->vhost = $vhost;
    }

    /**
     * Opens the connection and creates the AMQP context.
     *
     * @return AmqpConnectionFactory|null The connection factory instance or null on failure.
     */
    public function open(): ?AmqpConnectionFactory
    {
        if ($this->connection === null) {
            error_reporting(E_ALL ^ E_DEPRECATED ^ E_WARNING);
            $this->connection = new AmqpConnectionFactory([
                'host' => $this->host,
                'port' => $this->port,
                'vhost' =>  $this->vhost,
                'user' => $this->username,
                'pass' => $this->password,
                'persisted' => $this->persisted,
            ]);
            $this->context = $this->connection->createContext();
        }
        return $this->connection;
    }

    /**
     * Closes the AMQP context and connection.
     *
     * @return bool True if closed successfully, false otherwise.
     */
    public function close(): bool
    {
        if ($this->context !== null) {
            $this->context->close();
            unset($this->connection);
            $this->connection = null;
            return true;
        }
        return false;
    }

    /**
     * Re-establishes the connection to RabbitMQ.
     *
     * @return void
     */
    public function reconnect(): void
    {
        $this->close();
        $this->open();
    }

    /**
     * Declares an exchange with the given name and type.
     *
     * @param string $exchange The name of the exchange.
     * @param string $type The type of the exchange (direct, fanout, topic, headers).
     * @param int $flag Optional flags (e.g. durable).
     *
     * @return void
     */
    public function exchange(string $exchange, string $type = 'direct', int $flag = 2): void
    {
        if ($type !== 'direct' && $type !== 'fanout' && $type !== 'topic' && $type !== 'headers') {
            $type = AmqpTopic::TYPE_DIRECT;
        }
        if ($flag !== 0 && $flag !== 1 && $flag !== 2 && $flag !== 4 && $flag !== 8 && $flag !== 16) {
            $flag = AmqpQueue::FLAG_DURABLE;
        }
        $this->exchangeName = $exchange;
        $this->exchange = $this->context->createTopic($this->exchangeName);
        $this->exchange->addFlag($flag);
        $this->exchange->setType($type);
        $this->context->declareTopic($this->exchange);
    }

    /**
     * Declares a queue with the given name and optional arguments.
     *
     * @param string $queue The name of the queue.
     * @param int $flag Queue flags (e.g. durable).
     * @param array $args Additional arguments for the queue.
     *
     * @return int The result of declaring the queue.
     */
    public function queue(string $queue, int $flag = 2, array $args = [/*'x-max-priority' => 10*/]): int
    {
        if ($flag !== 0 && $flag !== 1 && $flag !== 2 && $flag !== 4 && $flag !== 8 && $flag !== 16) {
            $flag = AmqpQueue::FLAG_DURABLE;
        }
        $this->queueName = $queue;
        $this->queue = $this->context->createQueue($this->queueName);
        $this->queue->addFlag($flag);
        if (count($args) > 0) {
            $this->queue->setArguments($args);
        }
        return $this->context->declareQueue($this->queue);
    }

    /**
     * Binds the current queue to the configured exchange.
     *
     * @return void
     */
    public function queueBind(): void
    {
        $this->context->bind(new AmqpBind($this->exchange, $this->queue));
    }

    /**
     * Publishes a message to a queue or an exchange.
     *
     * @param string $message The message content.
     * @param string $type Target type: "queue" or "exchange".
     * @param int $ttl Optional time-to-live in milliseconds.
     * @param int $delay Optional delay in milliseconds.
     *
     * @return bool True on success, false otherwise.
     */
    public function pub(string $message, string $type, int $ttl = 0, int $delay = 0): bool
    {
        /** @var AmqpProducer $producer */
        $producer = $this->context->createProducer();
        if ($delay > 0) {
            $producer = $producer
                ->setDelayStrategy(new RabbitMqDlxDelayStrategy())
                ->setDeliveryDelay($delay);
        }
        if ($ttl > 0) {
            $producer = $producer->setTimeToLive($ttl);
        }
        /** @var AmqpMessage $amqpMessage */
        $amqpMessage = $this->context->createMessage($message);
        if ($type === "queue") {
            $producer->send($this->queue, $amqpMessage);
            return true;
        } else if ($type === "exchange") {
            $producer->send($this->exchange, $amqpMessage);
            return true;
        }
        return false;
    }

    /**
     * Publishes a message specifically to the exchange.
     *
     * @param string $message The message content.
     * @param int $ttl Optional time-to-live in milliseconds.
     * @param int $delay Optional delay in milliseconds.
     *
     * @return bool True on success, false otherwise.
     */
    public function pubExchange(string $message, int $ttl = 0, int $delay = 0): bool
    {
        return $this->pub($message, "exchange", $ttl, $delay);
    }

    /**
     * Publishes a message specifically to the queue.
     *
     * @param string $message The message content.
     * @param int $ttl Optional time-to-live in milliseconds.
     * @param int $delay Optional delay in milliseconds.
     *
     * @return bool True on success, false otherwise.
     */
    public function pubQueue(string $message, int $ttl = 0, int $delay = 0): bool
    {
        return $this->pub($message, "queue", $ttl, $delay);
    }

    /**
     * Subscribes to the queue and sets a callback for incoming messages.
     *
     * @param callable $callback The function to handle received messages.
     *
     * @return void
     */
    public function sub(callable $callback): void
    {
        $consumer = $this->context->createConsumer($this->queue);
        if ($this->subscriptionConsumer === null) {
            $this->subscriptionConsumer = $this->context->createSubscriptionConsumer();
        }
        $this->subscriptionConsumer->subscribe($consumer, function (Message $message, Consumer $consumer) use ($callback) {
            return $callback($message, $consumer);
        });
    }

    /**
     * Reads a single message from the queue.
     *
     * @return Message|null The received message or null if none.
     */
    public function readMessage(): ?Message
    {
        $consumer = $this->context->createConsumer($this->queue);
        return $consumer->receive();
    }

    /**
     * Starts the consumer and waits for messages to invoke the callback.
     *
     * @param int $time Optional timeout in milliseconds.
     *
     * @return void
     */
    public function waitCallbacks(int $time = 0): void
    {
        $this->subscriptionConsumer->consume($time);
    }
}
