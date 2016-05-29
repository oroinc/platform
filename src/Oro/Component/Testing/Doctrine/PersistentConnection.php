<?php
namespace Oro\Component\Testing\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\MySqlPlatform;

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
            $this->setConnected(true);
        } else {
            parent::connect();
            $this->setPersistentConnection($this->_conn);
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
        $this->wrapTransactionNestingLevel('rollBack');
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
            $rp = new \ReflectionProperty('Doctrine\DBAL\Connection', '_transactionNestingLevel');
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
        $exception = null;
        $this->setTransactionNestingLevel($this->getPersistentTransactionNestingLevel());
        try {
            call_user_func(array('parent', $method));
            $this->setPersistentTransactionNestingLevel($this->getTransactionNestingLevel());
        } catch (\Exception $e) {
            $this->setPersistentTransactionNestingLevel($this->getTransactionNestingLevel());
            throw $e;
        }
    }
    /**
     * @param bool $connected
     */
    protected function setConnected($connected)
    {
        static $rp = null;
        if (false == $rp) {
            $rp = new \ReflectionProperty('Doctrine\DBAL\Connection', '_isConnected');
            $rp->setAccessible(true);
        }

        $rp->setValue($this, $connected);
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
    /**
     * @param DriverConnection $connection
     */
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
    /**
     * @return DriverConnection
     */
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
        return md5(serialize($this->getParams()));
    }
}
