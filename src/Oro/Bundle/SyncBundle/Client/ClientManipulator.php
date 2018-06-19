<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Exception\ClientNotFoundException;
use Gos\Bundle\WebSocketBundle\Client\Exception\StorageException;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
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
     * @param ClientManipulatorInterface $decoratedClientManipulator
     * @param ClientStorageInterface $clientStorage
     * @param UserProvider $userProvider
     */
    public function __construct(
        ClientManipulatorInterface $decoratedClientManipulator,
        ClientStorageInterface $clientStorage,
        UserProvider $userProvider
    ) {
        $this->decoratedClientManipulator = $decoratedClientManipulator;
        $this->clientStorage = $clientStorage;
        $this->userProvider = $userProvider;
        $this->logger = new NullLogger();
    }

    /**
     * Overrides original implementation because Sync authentication tickets cannot be used for re-authentication.
     *
     * {@inheritDoc}
     *
     * @throws ClientNotFoundException
     * @throws StorageException
     */
    public function getClient(ConnectionInterface $connection)
    {
        try {
            $storageId = $this->clientStorage->getStorageId($connection);

            $user = $this->clientStorage->getClient($storageId);
        } catch (ClientNotFoundException $exception) {
            $this->logger->debug(
                'Client not found by storage id {storage_id} for connection {connection_id}',
                ['connection_id' => $connection->resourceId, 'storage_id' => $storageId, 'exception' => $exception]
            );

            if (!$this->renewClientByConnection($connection)) {
                throw $exception;
            }

            $user = $this->getClient($connection);
        } catch (StorageException $exception) {
            $this->logger->error(
                'Failed to get storage id from client storage for connection {connection_id}',
                ['connection_id' => $connection->resourceId, 'exception' => $exception]
            );

            throw $exception;
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function findByUsername(Topic $topic, $username)
    {
        return $this->decoratedClientManipulator->findByUsername($topic, $username);
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(Topic $topic, $anonymous = false)
    {
        return $this->decoratedClientManipulator->getAll($topic, $anonymous);
    }

    /**
     * {@inheritDoc}
     */
    public function findByRoles(Topic $topic, array $roles)
    {
        return $this->decoratedClientManipulator->findByRoles($topic, $roles);
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return bool
     */
    private function renewClientByConnection(ConnectionInterface $connection): bool
    {
        if (empty($connection->WAMP->username)) {
            $this->logger->error(
                'Username not found in connection {connection_id}',
                ['connection_id' => $connection->resourceId]
            );

            return false;
        }

        $username = $connection->WAMP->username;
        try {
            $user = $this->userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $exception) {
            $user = $connection->WAMP->username;
        }

        return $this->addUserToClientStorage($connection, $user);
    }

    /**
     * @param ConnectionInterface $connection
     * @param UserInterface|string $user
     *
     * @return bool
     */
    private function addUserToClientStorage(ConnectionInterface $connection, $user): bool
    {
        try {
            $storageId = $this->clientStorage->getStorageId($connection);
            $this->clientStorage->addClient($storageId, $user);

            return true;
        } catch (StorageException $exception) {
            $username = $user;
            if ($user instanceof UserInterface) {
                $username = $user->getUsername();
            }

            $this->logger->error(
                'Failed to add user to client storage for {username} for connection {connection_id}',
                ['connection_id' => $connection->resourceId, 'username' => $username, 'exception' => $exception]
            );
        }

        return false;
    }
}
