<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class RemoveEntityConfigEntityValueQuery implements MigrationQuery, ConnectionAwareInterface
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
     * @var Connection
     */
    protected $connection;

    /**
     * @param string $entityName
     * @param string $fieldName
     */
    public function __construct($entityName, $fieldName)
    {
        $this->entityName = $entityName;
        $this->fieldName = $fieldName;
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
        return 'Remove row value from oro_entity_config_index_value, oro_entity_config_field tables';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->removeEntityConfigIndexValue($logger);
        $this->removeEntityConfigField($logger);
    }

    /**
     * @param LoggerInterface $logger
     */
    protected function removeEntityConfigIndexValue(LoggerInterface $logger)
    {
        $sql = <<<QUERY
DELETE FROM oro_entity_config_index_value
WHERE field_id  = (
    SELECT id FROM oro_entity_config_field
    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class_name)
    AND field_name = :field_name
)
QUERY;

        $parameters = [
            'class_name' => $this->entityName,
            'field_name' => $this->fieldName
        ];

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);

        $logger->debug($sql);
    }

    /**
     * @param LoggerInterface $logger
     */
    protected function removeEntityConfigField(LoggerInterface $logger)
    {
        $sql = <<<QUERY
DELETE FROM oro_entity_config_field
WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class_name)
AND field_name = :field_name
QUERY;
        $parameters = [
            'class_name' => $this->entityName,
            'field_name' => $this->fieldName
        ];

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);

        $logger->debug($sql);
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
