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
     * @var DbalSchema
     */
    private $schema;

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
     * @param DbalSchema $schema
     * @param string $tableName
     * @param array $options
     */
    public function __construct(Connection $connection, DbalSchema $schema, $tableName, array $options = [])
    {
        $this->connection = $connection;
        $this->schema = $schema;
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
     * @return DbalSchema
     */
    public function getDBALSchema()
    {
        return $this->schema;
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
