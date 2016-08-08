<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

class DbalConnection implements ConnectionInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var
     */
    private $tableName;

    /**
     * @var array
     */
    private $options;

    /**
     * @param Connection $connection
     * @param string     $tableName
     * @param array      $options
     */
    public function __construct(Connection $connection, $tableName, array $options = [])
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalSession
     */
    public function createSession()
    {
        return new DbalSession($this);
    }

    /**
     * @return Connection
     */
    public function getDBALConnection()
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->connection->close();
    }
}
