<?php
namespace Oro\Component\Testing;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Client;

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
        if (false == $this->getClient() instanceof Client) {
            throw new \LogicException('The client must be instance of Client');
        }
        if (false == $this->getClient()->getContainer()) {
            throw new \LogicException('The client missing a container. Make sure the kernel was booted');
        }

        /** @var RegistryInterface $registry */
        $registry = $this->getClient()->getContainer()->get('doctrine');
        foreach ($registry->getManagers() as $name => $em) {
            if ($em instanceof EntityManagerInterface) {
                $em->clear();
                $connection = $em->getConnection();
                if ($connection->getNestTransactionsWithSavepoints() !== $nestTransactionsWithSavepoints) {
                    $connection->setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints);
                }
                $connection->beginTransaction();

                self::$dbIsolationConnections[$name.uniqid('connection', true)] = $connection;
            }
        }
    }

    /**
     * @internal
     */
    protected static function rollbackTransaction()
    {
        foreach (self::$dbIsolationConnections as $name => $connection) {
            while ($connection->isConnected() && $connection->isTransactionActive()) {
                $connection->rollBack();
            }
        }

        self::$dbIsolationConnections = [];
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    abstract protected function getClient();
}
