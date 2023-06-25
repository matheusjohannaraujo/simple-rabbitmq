<?php

namespace Lib;

// https://php-enqueue.github.io/transport/amqp/#purge-queue-messages
use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\Message;
use Interop\Queue\Consumer;


class SimpleRabbitMQ {

    private static $host = null;
    private static $port = null;
    private static $username = null;
    private static $password = null;
    private static $persisted = null;
    private static $vhost = null;
    public static $connection = null;
    public static $context = null;
    public $channel = null;
    public $exchange = null;
    public $exchangeName = "";
    public $queue = null;
    public $queueName = "";    
    public $subscriptionConsumer = null;

    public static function config(string $host = "localhost", string $port = "5672", $username = "user", $password = "password", bool $persisted = true, string $vhost = "/")
    {
        self::$host = $host;
        self::$port = $port;
        self::$username = $username;
        self::$password = $password;
        self::$persisted = $persisted;
        self::$vhost = $vhost;
    }

    public static function open()
    {
        if (self::$connection === null) {
            error_reporting(E_ALL ^ E_DEPRECATED ^ E_WARNING);
            self::$connection = new AmqpConnectionFactory([
                'host' => self::$host,
                'port' => self::$port,
                'vhost' =>  self::$vhost,
                'user' => self::$username,
                'pass' => self::$password,
                'persisted' => self::$persisted,
            ]);
            self::$context = self::$connection->createContext();
        }
        return self::$connection;
    }

    public function exchange(string $exchange)
    {
        $this->exchangeName = $exchange;
        $this->exchange = self::$context->createTopic($this->exchangeName);
        //$this->exchange->setType(AmqpTopic::TYPE_FANOUT);
        $this->exchange->setType(AmqpTopic::TYPE_DIRECT);
        return self::$context->declareTopic($this->exchange);
    }

    public function queue(string $queue)
    {
        $this->queueName = $queue;
        $this->queue = self::$context->createQueue($this->queueName);
        $this->queue->addFlag(AmqpQueue::FLAG_DURABLE);
        //$this->queue->setArguments(['x-max-priority' => 10]);
        return self::$context->declareQueue($this->queue);
    }

    public function queueBind()
    {
        return self::$context->bind(new AmqpBind($this->exchange, $this->queue));
    }    

    public function pub_exchange(string $message, int $time = 0)
    {
        $message = self::$context->createMessage($message);
        $producer = self::$context->createProducer();
        if ($time > 0) {
            $producer->setTimeToLive($time);
        }
        return $producer->send($this->exchange, $message);
    }

    public function pub_queue(string $message, int $time = 0)
    {
        $message = self::$context->createMessage($message);
        $producer = self::$context->createProducer();
        if ($time > 0) {
            $producer->setTimeToLive($time);
        }
        return $producer->send($this->queue, $message);
    }

    public function sub(callable $callback, int $time = 0)
    {
        $consumer = self::$context->createConsumer($this->queue);
        if ($this->subscriptionConsumer === null) {
            $this->subscriptionConsumer = self::$context->createSubscriptionConsumer();    
        }        
        $this->subscriptionConsumer->subscribe($consumer, function(Message $message, Consumer $consumer) use ($callback) {
            return $callback($message, $consumer);
        });
    }

    function readMessage()
    {
        $consumer = self::$context->createConsumer($this->queue);
        return $consumer->receive();
    }

    public function waitCallbacks(int $time = 0)
    {
        return $this->subscriptionConsumer->consume($time);
    }

}
