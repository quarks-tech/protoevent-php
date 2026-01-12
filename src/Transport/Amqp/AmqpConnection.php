<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp;

use QuarksTech\ProtoEvent\Exception\TransportException;

/**
 * AMQP connection wrapper with configuration
 */
final class AmqpConnection
{
    private \AMQPConnection $connection;

    public function __construct(
        string $host = 'localhost',
        int $port = 5672,
        string $user = 'guest',
        string $password = 'guest',
        string $vhost = '/',
        int $connectionTimeout = 10,
        int $readTimeout = 0,
        int $writeTimeout = 10,
        int $heartbeat = 60,
    ) {
        $this->connection = new \AMQPConnection([
            'host' => $host,
            'port' => $port,
            'login' => $user,
            'password' => $password,
            'vhost' => $vhost,
            'connect_timeout' => $connectionTimeout,
            'read_timeout' => $readTimeout,
            'write_timeout' => $writeTimeout,
            'heartbeat' => $heartbeat,
        ]);
    }

    public static function fromDsn(string $dsn): self
    {
        if (trim($dsn) === '') {
            throw new TransportException('DSN cannot be empty');
        }

        $parsed = parse_url($dsn);

        if ($parsed === false) {
            throw new TransportException("Invalid DSN: {$dsn}");
        }

        // Validate scheme
        $scheme = $parsed['scheme'] ?? '';
        if ($scheme !== '' && $scheme !== 'amqp' && $scheme !== 'amqps') {
            throw new TransportException("Invalid DSN scheme '{$scheme}', expected 'amqp' or 'amqps'");
        }

        // Host is required for a valid connection DSN
        if (!isset($parsed['host']) || $parsed['host'] === '') {
            throw new TransportException("DSN must contain a host: {$dsn}");
        }

        return new self(
            host: $parsed['host'],
            port: $parsed['port'] ?? 5672,
            user: isset($parsed['user']) ? urldecode($parsed['user']) : 'guest',
            password: isset($parsed['pass']) ? urldecode($parsed['pass']) : 'guest',
            vhost: isset($parsed['path']) ? ltrim(urldecode($parsed['path']), '/') : '/',
        );
    }

    public function connect(): void
    {
        if (!$this->connection->isConnected()) {
            $this->connection->connect();
        }
    }

    public function getVhost(): string
    {
        return $this->connection->getVhost();
    }

    public function getHost(): string
    {
        return $this->connection->getHost();
    }

    public function disconnect(): void
    {
        if ($this->connection->isConnected()) {
            $this->connection->disconnect();
        }
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function getConnection(): \AMQPConnection
    {
        return $this->connection;
    }
}
