<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEventListener as GosClientEventListener;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Guzzle\Http\Message\Response;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Decorates GOS ClientEventListener to add authentication of WAMP connection by Sync authentication tickets.
 */
class ClientEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var GosClientEventListener */
    private $decoratedClientEventListener;

    /** @var WebsocketAuthenticationProviderInterface */
    private $websocketAuthenticationProvider;

    /** @var ClientStorageInterface */
    private $clientStorage;

    /**
     * @param GosClientEventListener $decoratedClientEventListener
     * @param WebsocketAuthenticationProviderInterface $websocketAuthenticationProvider
     * @param ClientStorageInterface $clientStorage
     */
    public function __construct(
        GosClientEventListener $decoratedClientEventListener,
        WebsocketAuthenticationProviderInterface $websocketAuthenticationProvider,
        ClientStorageInterface $clientStorage
    ) {
        $this->decoratedClientEventListener = $decoratedClientEventListener;
        $this->websocketAuthenticationProvider = $websocketAuthenticationProvider;
        $this->clientStorage = $clientStorage;
        $this->logger = new NullLogger();
    }

    /**
     * @param ClientEvent $event
     *
     * @throws StorageException
     * @throws BadCredentialsException
     */
    public function onClientConnect(ClientEvent $event)
    {
        $connection = $event->getConnection();
        $connection->WAMP->clientStorageId = null;
        $connection->WAMP->username = null;

        $token = $this->websocketAuthenticationProvider->authenticate($connection);
        $loggerContext = [
            'connection_id' => $connection->resourceId,
            'session_id' => $connection->WAMP->sessionId,
            'username' => $token->getUsername(),
            'ticket_id' => $token->getCredentials(),
        ];

        try {
            $storageId = $this->clientStorage->getStorageId($connection);
            $this->clientStorage->addClient($storageId, $token->getUser());
        } catch (StorageException $exception) {
            $this->logger->error(
                'Failed to add user to client storage for {username} with ticket {ticketId}',
                ['exception' => $exception] + $loggerContext
            );

            throw $exception;
        }

        $connection->WAMP->clientStorageId = $storageId;

        // Save username in WAMP connection to be able to restore user in ClientManipulator in case of
        // ClientStorage TTL expiration.
        if ($token instanceof TicketToken) {
            $connection->WAMP->username = $token->getUsername();
        }

        $this->logger->info('{username} connected', ['storage_id' => $storageId] + $loggerContext);
    }

    /**
     * @param ClientEvent $event
     */
    public function onClientDisconnect(ClientEvent $event)
    {
        $this->decoratedClientEventListener->onClientDisconnect($event);
    }

    /**
     * @param ClientErrorEvent $event
     */
    public function onClientError(ClientErrorEvent $event)
    {
        $exception = $event->getException();
        $connection = $event->getConnection();

        $loggerContext = [
            'connection_id' => $connection->resourceId,
            'session_id' => $connection->WAMP->sessionId,
        ];

        if ($exception instanceof BadCredentialsException) {
            $event->stopPropagation();

            $this->closeConnection($connection, 403);

            $this->logger->info(
                'Authentication failed: {reason}',
                $loggerContext + ['reason' => $exception->getMessage()]
            );
        } else {
            $this->logger->error('Connection error occurred', $loggerContext + ['exception' => $exception]);
        }
    }

    /**
     * @param ClientRejectedEvent $event
     */
    public function onClientRejected(ClientRejectedEvent $event)
    {
        $this->decoratedClientEventListener->onClientRejected($event);
    }

    /**
     * @param ConnectionInterface $connection
     * @param int $status
     */
    private function closeConnection(ConnectionInterface $connection, int $status): void
    {
        $response = new Response($status);
        $connection->send((string)$response);
        $connection->close();
    }
}
