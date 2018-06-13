<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Adds logging facilities to websocket client.
 */
class LoggerAwareWebsocketClientDecorator implements WebsocketClientInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var WebsocketClientInterface */
    private $decoratedClient;

    /**
     * @param WebsocketClientInterface $decoratedClient
     */
    public function __construct(WebsocketClientInterface $decoratedClient)
    {
        $this->decoratedClient = $decoratedClient;

        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritDoc}
     */
    public function connect(): ?string
    {
        $result = null;

        try {
            $result = $this->decoratedClient->connect();

            $this->logger->debug('Connected to websocket server');
        } catch (BadResponseException $e) {
            $this->logBadResponseException($e);
        } catch (WebsocketException $e) {
            $this->logger->error('Could not connect to websocket server', [$e]);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        $result = $this->decoratedClient->disconnect();

        if ($result) {
            $this->logger->debug('Disconnected from websocket server');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return $this->decoratedClient->isConnected();
    }

    /**
     * {@inheritDoc}
     */
    public function publish(string $topicUri, $payload, array $exclude = [], array $eligible = []): bool
    {
        $result = false;

        try {
            $result = $this->decoratedClient->publish($topicUri, $payload, $exclude, $eligible);

            $this->logger->debug(sprintf('PUBLISH in %s websocket server', $topicUri));
        } catch (BadResponseException $e) {
            $this->logBadResponseException($e);
        } catch (WebsocketException $e) {
            $this->logWebsocketException($e);
        } catch (ValidationFailedException $e) {
            $this->logValidationFailedException($e);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function prefix(string $prefix, string $uri): bool
    {
        $result = false;

        try {
            $result = $this->decoratedClient->prefix($prefix, $uri);

            $this->logger->debug(sprintf('PREFIX %s in %s websocket server', $prefix, $uri));
        } catch (BadResponseException $e) {
            $this->logBadResponseException($e);
        } catch (WebsocketException $e) {
            $this->logWebsocketException($e);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function call(string $procUri, array $arguments = []): bool
    {
        $result = false;

        try {
            $result = $this->decoratedClient->call($procUri, $arguments);

            $this->logger->debug(sprintf('CALL in %s websocket server', $procUri));
        } catch (BadResponseException $e) {
            $this->logBadResponseException($e);
        } catch (WebsocketException $e) {
            $this->logWebsocketException($e);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function event(string $topicUri, $payload): bool
    {
        $result = false;

        try {
            $result = $this->decoratedClient->event($topicUri, $payload);

            $this->logger->debug(sprintf('EVENT in %s websocket server', $topicUri));
        } catch (BadResponseException $e) {
            $this->logBadResponseException($e);
        } catch (WebsocketException $e) {
            $this->logWebsocketException($e);
        } catch (ValidationFailedException $e) {
            $this->logValidationFailedException($e);
        }

        return $result;
    }

    /**
     * @param BadResponseException $e
     */
    private function logBadResponseException(BadResponseException $e): void
    {
        $this->logger->error('Error occurred while communicating with websocket server', [$e]);
    }

    /**
     * @param WebsocketException $e
     */
    private function logWebsocketException(WebsocketException $e): void
    {
        $this->logger->error('Could not send data to websocket server', [$e]);
    }

    /**
     * @param ValidationFailedException $e
     */
    private function logValidationFailedException(ValidationFailedException $e): void
    {
        $this->logger->error('Validation failed while trying to send payload to websocket server', [$e]);
    }
}
