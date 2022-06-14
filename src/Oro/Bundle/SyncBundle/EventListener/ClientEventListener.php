<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Decorates GOS ClientEventListener to add authentication of WAMP connection by Sync authentication tickets.
 */
class ClientEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private WebsocketAuthenticationProviderInterface $websocketAuthenticationProvider;

    private ClientStorageInterface $clientStorage;

    public function __construct(
        WebsocketAuthenticationProviderInterface $websocketAuthenticationProvider,
        ClientStorageInterface $clientStorage
    ) {
        $this->websocketAuthenticationProvider = $websocketAuthenticationProvider;
        $this->clientStorage = $clientStorage;
        $this->logger = new NullLogger();
    }

    /**
     * @throws StorageException
     * @throws BadCredentialsException
     */
    public function onClientConnect(ClientEvent $event)
    {
        $event->stopPropagation();

        $connection = $event->getConnection();
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
            $this->clientStorage->addClient($storageId, $token);
        } catch (StorageException $exception) {
            $this->logger->error(
                'Failed to add user to client storage for {username} with ticket {ticketId}',
                ['exception' => $exception] + $loggerContext
            );

            throw $exception;
        }

        // Save username in WAMP connection to be able to restore user in ClientManipulator in case of
        // ClientStorage TTL expiration.
        if ($token instanceof TicketToken) {
            $connection->WAMP->username = $token->getUsername();
        }

        $this->logger->info('{username} connected', ['storage_id' => $storageId] + $loggerContext);
    }

    public function onClientError(ClientErrorEvent $event)
    {
        $event->stopPropagation();

        $exception = $event->getThrowable();
        $connection = $event->getConnection();

        $loggerContext = [
            'connection_id' => $connection->resourceId,
            'session_id' => $connection->WAMP->sessionId,
        ];

        if ($exception instanceof BadCredentialsException) {
            $this->closeConnection($connection, 403);

            $this->logger->info(
                'Authentication failed: {reason}',
                $loggerContext + ['reason' => $exception->getMessage()]
            );
        } else {
            $this->logger->error('Connection error occurred', $loggerContext + ['exception' => $exception]);
        }
    }

    private function closeConnection(ConnectionInterface $connection, int $status): void
    {
        $response = new Response($status);
        $connection->send((string)$response);
        $connection->close();
    }
}
