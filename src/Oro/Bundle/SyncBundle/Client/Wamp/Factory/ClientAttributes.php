<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp\Factory;

/**
 * This class represents connection attributes which will be used to create an instance of WampClient.
 */
class ClientAttributes
{
    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var string */
    private $path;

    /**
     * Any registered socket transport returned by http://php.net/manual/en/function.stream-get-transports.php
     *
     * @var string
     */
    private $transport;

    /**
     * Will be passed to a context create function http://php.net/manual/en/function.stream-context-create.php
     *
     * @var array
     */
    private $contextOptions;

    public function __construct(
        string $host,
        int $port,
        string $path,
        string $transport,
        array $contextOptions
    ) {
        if ($host === '*') {
            $host = '127.0.0.1';
        }

        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->transport = $transport;
        $this->contextOptions = $contextOptions;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function getContextOptions(): array
    {
        return $this->contextOptions;
    }
}
