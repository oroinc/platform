<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Wamp\Client as GosClient;
use Gos\Component\WebSocketClient\Wamp\Protocol;

/**
 * Overrides GosClient to add the ability to set socket transport and context options.
 */
class WampClient extends GosClient
{
    /**
     * Will be passed to a context create function http://php.net/manual/en/function.stream-context-create.php
     *
     * @var array
     */
    private $contextOptions;

    /**
     * @param string $host
     * @param int $port
     * @param string $transport
     * @param array $contextOptions
     * @param string|null $origin
     */
    public function __construct(
        string $host,
        int $port,
        string $transport,
        array $contextOptions = [],
        ?string $origin = null
    ) {
        $secured = $this->isSecured($transport);

        parent::__construct($host, $port, $secured, $origin);

        $this->contextOptions = $contextOptions;
        $this->endpoint = "{$transport}://{$host}:{$port}";
    }

    /**
     * Overrides parent method to add ability to set socket context.
     *
     * {@inheritdoc}
     */
    public function connect($target = '/')
    {
        $this->target = '/' . ltrim($target, '/');

        if ($this->connected) {
            return $this->sessionId;
        }

        $this->socket = $this->openSocket();

        $response = $this->upgradeProtocol($this->target);

        $this->verifyResponse($response);

        $payload = json_decode($this->read());

        if ((int)$payload[0] !== Protocol::MSG_WELCOME) {
            throw new BadResponseException('WAMP Server did not send welcome message.');
        }

        $this->sessionId = $payload[1];

        $this->connected = true;

        return $this->sessionId;
    }

    /**
     * @param string $transport
     *
     * @return bool
     *
     * @extensionPoint to change the logic of websocket protocol detection.
     */
    protected function isSecured(string $transport): bool
    {
        return $transport === 'ssl' || stripos($transport, 'tls') === 0;
    }

    /**
     * @return resource
     *
     * @throws BadResponseException
     *
     * @extensionPoint to change the logic of socket creation.
     */
    protected function openSocket()
    {
        $socket = @stream_socket_client(
            $this->endpoint,
            $errno,
            $errstr,
            1,
            STREAM_CLIENT_CONNECT,
            stream_context_create($this->contextOptions)
        );

        if (!$socket) {
            throw new BadResponseException('Could not open socket. Reason: ' . $errstr);
        }

        return $socket;
    }
}
