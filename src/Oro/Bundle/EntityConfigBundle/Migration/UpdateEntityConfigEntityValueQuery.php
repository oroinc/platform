<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class UpdateEntityConfigEntityValueQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param string $entityName
     * @param string $scope
     * @param string $code
     * @param string $value
     */
    public function __construct($entityName, $scope, $code, $value)
    {
        $this->entityName = $entityName;
        $this->scope = $scope;
        $this->code = $code;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Set specific row value in oro_entity_config_index_value table';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        // update field itself
        $this->updateEntityConfigIndexValue($logger);

        // update entity config cached data
        $this->updateEntityConfig($logger);
    }

    /**
     * @param LoggerInterface $logger
     */
    protected function updateEntityConfigIndexValue(LoggerInterface $logger)
    {
        $sql =
            "UPDATE oro_entity_config_index_value
            SET value = ?
            WHERE
                entity_id = (SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1) AND
                field_id IS NULL AND
                scope = ? AND
                code = ?
            ";
        $parameters = [$this->value, $this->entityName, $this->scope, $this->code];
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);

        $logger->debug($sql);
    }

    /**
     * @param LoggerInterface $logger
     */
    protected function updateEntityConfig(LoggerInterface $logger)
    {
        $sql = 'SELECT data FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [$this->entityName];
        $data = $this->connection->fetchColumn($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $data = $data ? $this->connection->convertToPHPValue($data, Type::TARRAY) : [];
        $data[$this->scope][$this->code] = $this->value;
        $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

        $sql = 'UPDATE oro_entity_config SET data = ? WHERE class_name = ?';
        $parameters = [$data, $this->entityName];
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }

    /**
     * @param LoggerInterface $logger
     * @param string $sql
     * @param array $parameters
     */
    protected function logQuery(LoggerInterface $logger, $sql, array $parameters)
    {
        $message = sprintf('%s with parameters [%s]', $sql, implode(', ', $parameters));
        $logger->debug($message);
    }
}
