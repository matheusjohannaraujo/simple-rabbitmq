<?php

/*
	GitHub: https://github.com/matheusjohannaraujo/simple-rabbitmq
	Country: Brasil
	State: Pernambuco
	Developer: Matheus Johann Araujo
	Date: 2025-04-21
*/

namespace MJohann\Packlib\Facades;

use MJohann\Packlib\SimpleRabbitMQ as SimpleRabbitMQClass;

/**
 * Facade for the SimpleRabbitMQ providing static access to RabbitMQ operations.
 *
 * @method static void init(string $host = "localhost", int $port = 5672, string $username = "user", string $password = "password", bool $persisted = true, string $vhost = "/") Initializes a new SimpleRabbitMQ connection.
 * @method static SimpleRabbitMQ getInstance() Retrieves the current SimpleRabbitMQ connection instance.
 * @method static mixed __callStatic(string $method, array $arguments) Dynamically calls a method on the SimpleRabbitMQ instance.
 */
class SimpleRabbitMQ
{
    protected static ?SimpleRabbitMQClass $instance = null;

    /**
     * Initializes the SimpleRabbitMQ configuration parameters.
     *
     * @param string $host The hostname of the RabbitMQ server.
     * @param int $port The port number of the RabbitMQ server.
     * @param string $username The username for authentication.
     * @param string $password The password for authentication.
     * @param bool $persisted Whether the connection should be persistent.
     * @param string $vhost The virtual host to connect to.
     *
     * @return void
     */
    public static function init(): void
    {
        if (is_null(self::$instance)) {
            $reflection = new \ReflectionClass(SimpleRabbitMQClass::class);
            self::$instance = $reflection->newInstanceArgs(func_get_args());
            self::$instance->open();
        }
    }

    /**
     * Returns the singleton instance of SimpleRabbitMQ.
     * Throws an exception if the instance has not been initialized.
     *
     * @throws \Exception
     * @return SimpleRabbitMQ
     */
    public static function getInstance(): SimpleRabbitMQClass
    {
        if (is_null(self::$instance)) {
            throw new \Exception("Facade not initialized. Use \MJohann\Packlib\Facades\SimpleRabbitMQ::init([...]) first.");
        }

        return self::$instance;
    }

    /**
     * Magic method to forward static calls to the underlying SimpleRabbitMQ instance.
     * If the method does not exist on the instance, a BadMethodCallException is thrown.
     *
     * @param string $method
     * @param array $arguments
     * @throws \BadMethodCallException
     * @return mixed
     */
    public static function __callStatic($method, $arguments): mixed
    {
        $instance = self::getInstance();

        if (!method_exists($instance, $method)) {
            throw new \BadMethodCallException("Method {$method} not exist in SimpleRabbitMQ.");
        }

        return $instance->$method(...$arguments);
    }
}
