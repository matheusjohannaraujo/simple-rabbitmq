<?php

/*
	GitHub: https://github.com/matheusjohannaraujo/simple-rabbitmq
	Country: Brasil
	State: Pernambuco
	Developer: Matheus Johann Araujo
	Date: 2025-04-20
*/

namespace MJohann\Packlib\Facades;

use MJohann\Packlib\SimpleRabbitMQ as SimpleRabbitMQClass;

class SimpleRabbitMQ
{
    protected static ?SimpleRabbitMQClass $instance = null;

    /**
     * Configures RabbitMQ connection parameters.
     *
     * @param array{
     *     host: string,
     *     port: int,
     *     username: string,
     *     password: string,     
     *     persisted: bool,
     *     vhost: string
     * } $args
     * @return void
     */
    public static function init(array $args = []): void
    {
        if (is_null(self::$instance)) {
            $reflection = new \ReflectionClass(SimpleRabbitMQClass::class);
            self::$instance = $reflection->newInstanceArgs($args);
            self::$instance->open();
        }
    }

    protected static function getInstance(): SimpleRabbitMQClass
    {
        if (is_null(self::$instance)) {
            throw new \Exception("Facade not initialized. Use \MJohann\Packlib\Facades\SimpleRabbitMQ::init([...]) first.");
        }

        return self::$instance;
    }

    public static function __callStatic($method, $arguments)
    {
        $instance = self::getInstance();

        if (!method_exists($instance, $method)) {
            throw new \BadMethodCallException("Method {$method} not exist in SimpleRabbitMQ.");
        }

        return $instance->$method(...$arguments);
    }
}
