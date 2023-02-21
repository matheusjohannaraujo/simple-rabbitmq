<?php

namespace Lib;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class SimpleRabbitMQ {

    public static $connection = null;
    public $channel = null;
    public $queue = null;
    public $queueName = null;
    private $host = null;
    private $port = null;
    private $username = null;
    private $password = null;

    public function __construct(string $host = "localhost", string $port = "5672", $username = "user", $password = "password")
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function open()
    {
        if (self::$connection === null) {
            self::$connection = new AMQPStreamConnection($this->host, $this->port, $this->username, $this->password);
        }
        return self::$connection;
    }

    public function close()
    {
        if (self::$connection !== null) {
            return self::$connection->close();
        }
        return false;
    }

    /**
     * $channel é uma instância da classe PhpAmqpLib\Channel\AMQPChannel, que representa um canal de comunicação com o servidor do RabbitMQ.
     */
    public function openChannel()
    {
        $this->open();
        if (self::$connection !== null) {
            return $this->channel = self::$connection->channel();
        }
        return null;        
    }

    public function closeChannel()
    {
        if ($this->channel !== null) {
            $this->channel->close();
            $this->channel = null;
            return true;
        }
        return false;
    }

    /**
     * 
     * $queue (string): o nome da fila a ser declarada. Se este argumento for deixado em branco, o RabbitMQ criará uma fila exclusiva com um nome gerado automaticamente.
     * $passive (bool): se definido como true, o RabbitMQ irá verificar se a fila já existe sem tentar criar uma nova. Se a fila não existir, o RabbitMQ irá retornar um erro. O valor padrão é false.
     * $durable (bool): se definido como true, a fila será persistente. Isso significa que, se o servidor do RabbitMQ for reiniciado, a fila ainda existirá. O valor padrão é false.
     * $exclusive (bool): se definido como true, a fila será exclusiva para a conexão atual. Isso significa que a fila só pode ser acessada pela conexão que a criou. O valor padrão é false.
     * $auto_delete (bool): se definido como true, a fila será automaticamente excluída pelo RabbitMQ quando não tiver mais consumidores ou quando a conexão que a criou for fechada. O valor padrão é false.
    */
    public function queue(string $queue, bool $passive = false, bool $durable = true, bool $exclusive = false, bool $auto_delete = false)
    {
        $this->queueName = $queue;
        return $this->queue = $this->channel->queue_declare($queue, $passive, $durable, $exclusive, $auto_delete);
    }

    public function queueSize()
    {
        return $this->queue[1] ?? -1;
    }    

    public function pub(string $message, string $exchange = null, string $routing_key = null)
    {
        if ($exchange === null) {
            $exchange = "";
        }
        if ($routing_key === null) {
            $routing_key = $this->queueName;
        }
        return $this->channel->basic_publish(new AMQPMessage($message), $exchange, $routing_key);
    }

    /**
     * $queue: O nome da fila da qual o consumidor receberá as mensagens.
     * $consumer_tag: Uma tag que identifica o consumidor. Se essa tag não for fornecida, o RabbitMQ gerará uma tag aleatória para o consumidor.
     * $no_local: Quando definido como true, indica que as mensagens publicadas pelo próprio consumidor não devem ser entregues a ele.
     * $no_ack: Quando definido como true, indica que o RabbitMQ não deve esperar uma confirmação de recebimento de mensagens pelo consumidor. Isso significa que as mensagens serão consideradas automaticamente confirmadas e removidas da fila após serem entregues ao consumidor.
     * $exclusive: Quando definido como true, indica que a fila só deve ser usada por este consumidor e será excluída quando o consumidor se desconectar.
     * $nowait: Quando definido como true, indica que a chamada não deve esperar a resposta do RabbitMQ.
     * $callback: Uma função de retorno que será chamada pelo RabbitMQ quando uma nova mensagem for entregue ao consumidor. Essa função deve aceitar um argumento do tipo \PhpAmqpLib\Message\AMQPMessage, que contém a mensagem entregue.
     */
    public function sub(callable $callback, string $consumer_tag = "", bool $no_local = false, bool $no_ack = false, bool $exclusive = false, bool $nowait = false)
    {
        $cb = $callback;
        if (!$no_ack) {
            $channel = &$this->channel;
            $cb = function ($msg) use ($callback, $channel) {
                if ($callback($msg) === true) {
                    $channel->basic_ack($msg->delivery_info['delivery_tag']);
                }            
            };
        }
        return $this->channel->basic_consume($this->queueName, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, $cb);
    }

    function readMessage()
    {
        return $this->channel->wait();
    }

    function readAllMessages()
    {
        $queueCountMessages = $this->queueSize();
        for ($i = 0; $i < $queueCountMessages; $i++) {
            $this->readMessage();
        }
    }

    public function waitCallbacks(int $sleep = 0)
    {
        while ($this->channel !== null && count($this->channel->callbacks)) {
            $this->readMessage();
            if ($sleep > 0) {
                usleep($sleep);
            }
        }
    }

}
