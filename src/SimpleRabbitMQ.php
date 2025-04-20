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
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\Impl\AmqpBind;
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
    public ?AmqpConnectionFactory $connection = null;
    public $context = null;
    public $channel = null;
    public $exchange = null;
    public $exchangeName = "";
    public $queue = null;
    public $queueName = "";
    public $subscriptionConsumer = null;

    public function __construct(string $host = "localhost", int $port = 5672, $username = "user", $password = "password", bool $persisted = true, string $vhost = "/")
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->persisted = $persisted;
        $this->vhost = $vhost;
    }

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

    public function close()
    {
        if ($this->context !== null) {
            $this->context->close();
            unset($this->connection);
            $this->connection = null;
            return true;
        }
        return false;
    }

    public function exchange(string $exchange, string $type = 'direct', int $flag = 2)
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
        return $this->context->declareTopic($this->exchange);
    }

    public function queue(string $queue, int $flag = 2, array $args = [/*'x-max-priority' => 10*/])
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

    public function queueBind()
    {
        return $this->context->bind(new AmqpBind($this->exchange, $this->queue));
    }

    public function pub(string $message, string $type, int $ttl = 0, int $delay = 0)
    {
        $message = $this->context->createMessage($message);
        $producer = $this->context->createProducer();
        if ($delay > 0) {
            $producer = $producer
                ->setDelayStrategy(new RabbitMqDlxDelayStrategy())
                ->setDeliveryDelay($delay);
        }
        if ($ttl > 0) {
            $producer = $producer->setTimeToLive($ttl);
        }
        if ($type === "queue") {
            return $producer->send($this->queue, $message);
        } else if ($type === "exchange") {
            return $producer->send($this->exchange, $message);
        }
        return false;
    }

    public function pub_exchange(string $message, int $ttl = 0, int $delay = 0)
    {
        return $this->pub($message, "exchange", $ttl, $delay);
    }

    public function pub_queue(string $message, int $ttl = 0, int $delay = 0)
    {
        return $this->pub($message, "queue", $ttl, $delay);
    }

    public function sub(callable $callback, int $time = 0)
    {
        $consumer = $this->context->createConsumer($this->queue);
        if ($this->subscriptionConsumer === null) {
            $this->subscriptionConsumer = $this->context->createSubscriptionConsumer();
        }
        $this->subscriptionConsumer->subscribe($consumer, function (Message $message, Consumer $consumer) use ($callback) {
            return $callback($message, $consumer);
        });
    }

    function readMessage()
    {
        $consumer = $this->context->createConsumer($this->queue);
        return $consumer->receive();
    }

    public function waitCallbacks(int $time = 0)
    {
        return $this->subscriptionConsumer->consume($time);
    }
}
