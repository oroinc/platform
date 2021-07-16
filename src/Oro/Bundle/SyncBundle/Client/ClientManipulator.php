<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use GuzzleHttp\Psr7\Uri;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Overrides original implementation because Sync authentication tickets do not have TTL thus do not expire.
 */
class ClientManipulator implements ClientManipulatorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ClientManipulatorInterface
     */
    private $decoratedClientManipulator;

    /**
     * @var ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * @var TicketProviderInterface
     */
    private $ticketProvider;

    /**
     * @var WebsocketAuthenticationProviderInterface
     */
    private $websocketAuthenticationProvider;

    public function __construct(
        ClientManipulatorInterface $decoratedClientManipulator,
        ClientStorageInterface $clientStorage,
        UserProvider $userProvider,
        TicketProviderInterface $ticketProvider,
        WebsocketAuthenticationProviderInterface $websocketAuthenticationProvider
    ) {
        $this->decoratedClientManipulator = $decoratedClientManipulator;
        $this->clientStorage = $clientStorage;
        $this->userProvider = $userProvider;
        $this->logger = new NullLogger();
        $this->ticketProvider = $ticketProvider;
        $this->websocketAuthenticationProvider = $websocketAuthenticationProvider;
    }

    /**
     * Overrides original implementation because Sync authentication tickets cannot be used for re-authentication.
     *
     * {@inheritDoc}
     *
     * @throws ClientNotFoundException
     * @throws StorageException
     */
    public function getClient(ConnectionInterface $connection): TokenInterface
    {
        $storageId = $this->clientStorage->getStorageId($connection);
        try {
            $token = $this->clientStorage->getClient($storageId);
        } catch (ClientNotFoundException $exception) {
            $this->logger->debug(
                'Client not found by storage id {storage_id}',
                ['storage_id' => $storageId, 'exception' => $exception]
            );

            if (!$this->renewClientByConnection($connection)) {
                throw $exception;
            }

            $token = $this->getClient($connection);
        } catch (StorageException $exception) {
            $this->logger->error(
                'Client storage failed when trying to get client by storage id {storage_id}',
                ['storage_id' => $storageId, 'exception' => $exception]
            );

            throw $exception;
        }

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(Topic $topic, bool $anonymous = false): array
    {
        return $this->decoratedClientManipulator->getAll($topic, $anonymous);
    }

    /**
     * {@inheritDoc}
     */
    public function findByRoles(Topic $topic, array $roles): array
    {
        return $this->decoratedClientManipulator->findByRoles($topic, $roles);
    }

    /**
     * {@inheritDoc}
     */
    public function findAllByUsername(Topic $topic, string $username): array
    {
        return $this->decoratedClientManipulator->findAllByUsername($topic, $username);
    }

    /**
     * {@inheritDoc}
     */
    public function getUser(ConnectionInterface $connection)
    {
        return $this->getClient($connection)->getUser();
    }

    private function renewClientByConnection(ConnectionInterface $connection): bool
    {
        if (empty($connection->WAMP->username)) {
            $this->logger->error(
                'Username not found in connection {storage_id}',
                ['storage_id' => $this->clientStorage->getStorageId($connection)]
            );

            return false;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($connection->WAMP->username);

            return $this->addUserToClientStorage($connection, $user);
        } catch (UsernameNotFoundException $exception) {
            return $this->addUserToClientStorage($connection);
        }
    }

    private function addUserToClientStorage(ConnectionInterface $connection, ?UserInterface $user = null): bool
    {
        if (!isset($connection->httpRequest)) {
            return false;
        }

        $connection->httpRequest = $this->getRequestWithNewTicket($connection->httpRequest, $user);
        $token = $this->websocketAuthenticationProvider->authenticate($connection);

        $storageId = $this->clientStorage->getStorageId($connection);
        try {
            $this->clientStorage->addClient($storageId, $token);

            return true;
        } catch (StorageException $exception) {
            $username = $token->getUsername();

            $this->logger->error(
                'Failed to add user to client storage for {username} for connection {storage_id}',
                ['storage_id' => $storageId, 'username' => $username, 'exception' => $exception]
            );
        }

        return false;
    }

    private function getRequestWithNewTicket(RequestInterface $request, ?UserInterface $user = null): RequestInterface
    {
        $ticket = base64_encode($this->ticketProvider->generateTicket($user));
        $requestUri = Uri::withQueryValue($request->getUri(), 'ticket', $ticket);

        return $request->withUri($requestUri);
    }
}
