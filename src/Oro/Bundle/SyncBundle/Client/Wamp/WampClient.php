<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Gos\Component\WebSocketClient\Wamp\ClientInterface;
use Gos\Component\WebSocketClient\Wamp\PayloadGenerator;
use Gos\Component\WebSocketClient\Wamp\PayloadGeneratorInterface;
use Gos\Component\WebSocketClient\Wamp\Protocol;
use Gos\Component\WebSocketClient\Wamp\WebsocketPayload;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Overrides GosClient to add the ability to set socket transport and context options.
 * Overridden: connect.
 * Other methods are copied from original Gos\Component\WebSocketClient\Wamp\Client because of final.
 */
class WampClient implements ClientInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var bool
     */
    private $connected = false;

    /**
     * @var bool
     */
    private $closing = false;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string|null
     */
    private $target;

    /**
     * @var resource|null
     */
    private $socket;

    /**
     * @var string|null
     */
    private $sessionId;

    /**
     * @var string
     */
    private $serverHost;

    /**
     * @var int
     */
    private $serverPort;

    /**
     * @var bool
     */
    private $secured;

    /**
     * @var string|null
     */
    private $origin;

    /**
     * @var PayloadGeneratorInterface
     */
    private $payloadGenerator;

    /**
     * Will be passed to a context create function http://php.net/manual/en/function.stream-context-create.php
     *
     * @var array
     */
    private $contextOptions;

    /**
     * @var ClientInterface
     */
    private $gosClient;

    public function __construct(
        string $host,
        int $port,
        string $transport,
        array $contextOptions = [],
        ?string $origin = null
    ) {
        $this->serverHost = $host;
        $this->serverPort = $port;
        $this->secured = $this->isSecured($transport);
        $this->origin = $origin ?? $host;
        $this->payloadGenerator = new PayloadGenerator();
        $this->contextOptions = $contextOptions;
        $this->endpoint = "{$transport}://{$host}:{$port}";
    }

    /**
     * Overrides parent method to add ability to set socket context.
     *
     * {@inheritdoc}
     */
    public function connect(string $target = '/'): string
    {
        $this->target = '/' . ltrim($target, '/');

        if ($this->connected) {
            return (string) $this->sessionId;
        }

        $this->socket = $this->openSocket();

        $response = $this->upgradeProtocol($this->target);

        $this->verifyResponse($response);

        $payload = json_decode($this->read(), true);
        if (isset($payload[0], $payload[1])) {
            if ((int)$payload[0] !== Protocol::MSG_WELCOME) {
                throw new BadResponseException('WAMP Server did not send welcome message.');
            }

            $this->sessionId = $payload[1];
            $this->connected = true;
        }

        return (string) $this->sessionId;
    }

    /**
     * Copy of Client::disconnect
     *
     * @throws WebsocketException if the connection could not be disconnected cleanly
     */
    public function disconnect(): bool
    {
        if (false === $this->connected) {
            return true;
        }

        if (null === $this->socket) {
            return true;
        }

        $this->send($this->payloadGenerator->generateClosePayload(), WebsocketPayload::OPCODE_CLOSE);

        $firstByte = fread($this->socket, 1);

        if (false === $firstByte) {
            if (null !== $this->logger) {
                $this->logger->error('Could not extract the payload from the buffer.', ['error' => error_get_last()]);
            }

            throw new WebsocketException('Could not extract the payload from the buffer.');
        }

        $payloadLength = \ord($firstByte);
        $payload = fread($this->socket, $payloadLength);

        if (false === $payload) {
            if (null !== $this->logger) {
                $this->logger->error('Could not extract the payload from the buffer.', ['error' => error_get_last()]);
            }

            throw new WebsocketException('Could not extract the payload from the buffer.');
        }

        if ($payloadLength >= 2) {
            $bin = $payload[0] . $payload[1];
            $status = bindec(sprintf('%08b%08b', \ord($payload[0]), \ord($payload[1])));

            $this->send($bin . 'Close acknowledged: ' . $status, WebsocketPayload::OPCODE_CLOSE);
        }

        fclose($this->socket);
        $this->connected = false;

        return true;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Copy of Client::prefix
     *
     * Establish a prefix on server.
     *
     * @see http://wamp.ws/spec#prefix_message
     *
     * {@inheritDoc}
     */
    public function prefix(string $prefix, string $uri): void
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf('Establishing prefix "%s" for URI "%s"', $prefix, $uri));
        }

        $this->send([Protocol::MSG_PREFIX, $prefix, $uri]);
    }

    /**
     * Copy of Client::call
     *
     * Call a procedure on server.
     *
     * @see http://wamp.ws/spec#call_message
     *
     * @param array|mixed $args Arguments for the message either as an array or variadic set of parameters
     */
    public function call(string $procUri, $args): void
    {
        if (!\is_array($args)) {
            $args = \func_get_args();
            array_shift($args);
        }

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('Websocket client calling %s', $procUri),
                [
                    'callArguments' => $args,
                ]
            );
        }

        $this->send(
            array_merge(
                [Protocol::MSG_CALL, uniqid('', true), $procUri],
                $args
            )
        );
    }

    /**
     * Copy of Client::publish
     *
     * The client will send an event to all clients connected to the server who have subscribed to the topicURI.
     *
     * @see http://wamp.ws/spec#publish_message
     *
     * @param string[] $exclude
     * @param string[] $eligible
     */
    public function publish(string $topicUri, string $payload, array $exclude = [], array $eligible = []): void
    {
        $payload = json_decode($payload, JSON_OBJECT_AS_ARRAY);
        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('Websocket client publishing to %s', $topicUri),
                [
                    'payload' => $payload,
                    'excludedIds' => $exclude,
                    'eligibleIds' => $eligible,
                ]
            );
        }

        $this->send([Protocol::MSG_PUBLISH, $topicUri, $payload, $exclude, $eligible]);
    }

    /**
     * Copy of Client::event
     *
     * Subscribers receive PubSub events published by subscribers via the EVENT message.
     * The EVENT message contains the topicURI, the topic under which the event was published,
     * and event, the PubSub event payload.
     *
     * {@inheritDoc}
     */
    public function event(string $topicUri, string $payload): void
    {
        $payload = json_decode($payload, JSON_OBJECT_AS_ARRAY);
        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('Websocket client sending event to %s', $topicUri),
                [
                    'payload' => $payload,
                ]
            );
        }

        $this->send([Protocol::MSG_EVENT, $topicUri, $payload]);
    }

    /**
     * {@inheritdoc}
     *
     * Taken from the 1.0 version of WAMP client of GeniusesOfSymfony/WebSocketPhpClient.
     *
     * @throws BadResponseException
     */
    protected function read(): string
    {
        $streamBody = stream_get_contents($this->socket, stream_get_meta_data($this->socket)['unread_bytes']);
        if (false === $streamBody) {
            throw new BadResponseException('The stream buffer could not be read.');
        }

        $startPos = strpos($streamBody, '[');
        $endPos = strpos($streamBody, ']');

        if (false === $startPos || false === $endPos) {
            throw new BadResponseException('Could not extract response body from stream.');
        }

        return substr($streamBody, $startPos, $endPos);
    }

    /**
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
        try {
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
        } catch (\ErrorException $exception) {
            throw new BadResponseException('Could not open socket. Reason: ' . $errstr, 0, $exception);
        }

        return $socket;
    }

    /**
     * Copy of Client::send
     *
     * @param mixed $data Any JSON encodable data
     * @param int $opcode
     * @throws BadResponseException
     * @throws WebsocketException if the data cannot be encoded properly
     */
    private function send($data, int $opcode = WebsocketPayload::OPCODE_TEXT): void
    {
        if (\is_array($data)) {
            $payload = json_encode($data);

            if (false === $payload) {
                throw new WebsocketException('The data could not be encoded: ' . json_last_error_msg());
            }
        } elseif (is_scalar($data)) {
            $payload = $data;
        } else {
            throw new WebsocketException('The data must be an array or a scalar value.');
        }

        $encoded = $this->payloadGenerator->encode(
            (new WebsocketPayload())
                ->setOpcode($opcode)
                ->setMask(0x1)
                ->setPayload($payload)
        );

        // Check if the connection was reset, if so try to reconnect
        if (0 === @fwrite($this->socket, $encoded)) {
            $this->connected = false;
            $this->connect($this->target);

            fwrite($this->socket, $encoded);
        }
    }

    /**
     * Copy of Client::upgradeProtocol
     * @param string $target
     * @return string|false Response body from the request or boolean false on failure
     *
     * @throws WebsocketException if the target URI is invalid
     */
    private function upgradeProtocol(string $target)
    {
        $key = $this->generateKey();

        if (!str_contains($target, '/')) {
            if (null !== $this->logger) {
                $this->logger->error('Invalid target path for WAMP server.', ['target' => $target]);
            }

            throw new WebsocketException('WAMP server target must contain a "/"');
        }

        $protocol = $this->secured ? 'wss' : 'ws';

        $out = "GET {$protocol}://{$this->serverHost}:{$this->serverPort}{$target} HTTP/1.1\r\n";
        $out .= "Host: {$this->serverHost}:{$this->serverPort}\r\n";
        $out .= "Pragma: no-cache\r\n";
        $out .= "Cache-Control: no-cache\r\n";
        $out .= "Upgrade: WebSocket\r\n";
        $out .= "Connection: Upgrade\r\n";
        $out .= "Sec-WebSocket-Key: $key\r\n";
        $out .= "Sec-WebSocket-Protocol: wamp\r\n";
        $out .= "Sec-WebSocket-Version: 13\r\n";
        $out .= "Origin: {$this->origin}\r\n\r\n";

        fwrite($this->socket, $out);

        return fgets($this->socket);
    }

    /**
     * Copy of Client::generateKey
     */
    private function generateKey(int $length = 16): string
    {
        $c = 0;
        $tmp = '';

        while ($c++ * 16 < $length) {
            $tmp .= md5((string)mt_rand(), true);
        }

        return base64_encode(substr($tmp, 0, $length));
    }

    /**
     * Copy of Client::verifyResponse
     * @param string|false $response Response body from the upgrade request or boolean false on failure
     *
     * @throws BadResponseException if an invalid response was received
     */
    private function verifyResponse($response): void
    {
        if (false === $response) {
            if (null !== $this->logger) {
                $this->logger->error('WAMP Server did not respond properly');
            }

            throw new BadResponseException('WAMP Server did not respond properly');
        }

        $responseStatus = substr($response, 0, 12);

        if ('HTTP/1.1 101' !== $responseStatus) {
            if (null !== $this->logger) {
                $this->logger->error('Unexpected HTTP response from WAMP server.', ['response' => $response]);
            }

            throw new BadResponseException(
                sprintf('Unexpected response status. Expected "HTTP/1.1 101", got "%s".', $responseStatus)
            );
        }
    }
}
