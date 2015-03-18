<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class UpdateEntityConfigFieldValueQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $fieldName;

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
     * @param string $fieldName
     * @param string $scope
     * @param string $code
     * @param string $value
     */
    public function __construct($entityName, $fieldName, $scope, $code, $value)
    {
        $this->entityName = $entityName;
        $this->fieldName = $fieldName;
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
        return 'Update entity config value for specific field';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';
        $parameters = [$this->entityName, $this->fieldName];
        $row = $this->connection->fetchAssoc($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        if ($row) {
            $data = $row['data'];
            $id = $row['id'];
            $data = $data ? $this->connection->convertToPHPValue($data, Type::TARRAY) : [];
            $data[$this->scope][$this->code] = $this->value;
            $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

            // update field itself
            $sql = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
            $parameters = [$data, $id];
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
            $this->logQuery($logger, $sql, $parameters);
        }
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
