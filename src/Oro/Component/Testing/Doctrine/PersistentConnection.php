<?php

namespace Oro\Component\Testing\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\TransactionIsolationLevel;

/**
 * Connection wrapper sharing the same db handle across multiple requests
 *
 * Allows multiple Connection instances to run in the same transaction
 */
class PersistentConnection extends Connection
{
    /**
     * @var DriverConnection[]
     */
    protected static $persistentConnections;
    /**
     * @var int[]
     */
    protected static $persistentTransactionNestingLevels;

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return false;
        }
        if ($this->hasPersistentConnection()) {
            $this->_conn = $this->getPersistentConnection();
        } else {
            parent::connect();

            $this->setPersistentConnection($this->_conn);

            if ($this->getTransactionIsolation() !== TransactionIsolationLevel::READ_COMMITTED) {
                $this->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);
            }

            if ($this->getDatabasePlatform() instanceof MySqlPlatform) {
                // force default value
                $this->_conn->exec('SET SESSION wait_timeout=28800');
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close($force = false)
    {
        if ($force) {
            parent::close();
            $this->unsetPersistentConnection();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->wrapTransactionNestingLevel('beginTransaction');
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->wrapTransactionNestingLevel('commit');
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        try {
            $this->wrapTransactionNestingLevel('rollBack');
        } catch (\PDOException $exception) {
            if ($this->getDatabasePlatform() instanceof MySqlPlatform) {
                // For MySql transactions with DDL are committed automatically and rollBack throws \PDOException,
                // that is ignored to make DB isolation work the same for all supported DB platforms.
                $this->setPersistentTransactionNestingLevel($this->getTransactionNestingLevel());
            } else {
                throw $exception;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isTransactionActive()
    {
        $this->setTransactionNestingLevel($this->getPersistentTransactionNestingLevel());

        return parent::isTransactionActive();
    }

    /**
     * @param int $level
     */
    private function setTransactionNestingLevel($level)
    {
        static $rp = null;
        if (false == $rp) {
            $rp = new \ReflectionProperty('Doctrine\DBAL\Connection', 'transactionNestingLevel');
            $rp->setAccessible(true);
        }

        $rp->setValue($this, $level);
    }

    /**
     * @param string $method
     *
     * @throws \Exception
     */
    private function wrapTransactionNestingLevel($method)
    {
        $this->setTransactionNestingLevel($this->getPersistentTransactionNestingLevel());
        parent::$method();
        $this->setPersistentTransactionNestingLevel($this->getTransactionNestingLevel());
    }

    /**
     * @return int
     */
    protected function getPersistentTransactionNestingLevel()
    {
        if (isset(static::$persistentTransactionNestingLevels[$this->getConnectionId()])) {
            return static::$persistentTransactionNestingLevels[$this->getConnectionId()];
        }

        return 0;
    }

    /**
     * @param int $level
     */
    protected function setPersistentTransactionNestingLevel($level)
    {
        static::$persistentTransactionNestingLevels[$this->getConnectionId()] = $level;
    }

    protected function setPersistentConnection(DriverConnection $connection)
    {
        static::$persistentConnections[$this->getConnectionId()] = $connection;
    }

    /**
     * @return bool
     */
    protected function hasPersistentConnection()
    {
        return isset(static::$persistentConnections[$this->getConnectionId()]);
    }

    /**
     * @return DriverConnection
     */
    protected function getPersistentConnection()
    {
        return static::$persistentConnections[$this->getConnectionId()];
    }

    protected function unsetPersistentConnection()
    {
        unset(static::$persistentConnections[$this->getConnectionId()]);
        unset(static::$persistentTransactionNestingLevels[$this->getConnectionId()]);
    }

    /**
     * @return string
     */
    protected function getConnectionId()
    {
        $params = $this->getParams();
        unset($params['wrapperClass']);

        return $params['driverOptions']['ConnectionId'] ?? md5(serialize($params));
    }
}
