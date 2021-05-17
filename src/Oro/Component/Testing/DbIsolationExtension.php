<?php

namespace Oro\Component\Testing;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Testing\Doctrine\Events;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * Provides functions to operate DB transactions.
 */
trait DbIsolationExtension
{
    /**
     * @var Connection[]
     */
    protected static $dbIsolationConnections = [];

    /**
     * @param bool $nestTransactionsWithSavepoints
     *
     * @internal
     */
    protected function startTransaction($nestTransactionsWithSavepoints = false)
    {
        if (false == $this->getClientInstance() instanceof Client) {
            throw new \LogicException('The client must be instance of Client');
        }
        if (false == $this->getClientInstance()->getContainer()) {
            throw new \LogicException('The client missing a container. Make sure the kernel was booted');
        }

        /** @var ManagerRegistry $registry */
        $registry = $this->getClientInstance()->getContainer()->get('doctrine');
        foreach ($registry->getManagers() as $name => $em) {
            if ($em instanceof EntityManagerInterface) {
                $objectId = spl_object_id($em->getConnection());
                if (array_key_exists($objectId, self::$dbIsolationConnections)) {
                    continue;
                }

                $em->clear();
                $connection = $em->getConnection();
                if ($connection->getNestTransactionsWithSavepoints() !== $nestTransactionsWithSavepoints) {
                    $connection->setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints);
                }
                $connection->beginTransaction();

                self::$dbIsolationConnections[$objectId] = $connection;
            }
        }
    }

    /**
     * @internal
     */
    protected static function rollbackTransaction()
    {
        foreach (array_reverse(self::$dbIsolationConnections) as $connection) {
            $rolledBack = false;
            while ($connection->isConnected() && $connection->isTransactionActive()) {
                $connection->rollBack();
                $rolledBack = true;
            }
            if ($rolledBack) {
                $args = new ConnectionEventArgs($connection);
                $connection->getEventManager()->dispatchEvent(Events::ON_AFTER_TEST_TRANSACTION_ROLLBACK, $args);
            }
        }

        self::$dbIsolationConnections = [];
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    abstract protected static function getClientInstance();
}
