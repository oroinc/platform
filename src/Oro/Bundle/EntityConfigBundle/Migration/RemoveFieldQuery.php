<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class RemoveFieldQuery extends ParametrizedMigrationQuery
{
    /** @var string  */
    protected $entityClass;

    /** @var string  */
    protected $entityField;

    /**
     * @param string $entityClass
     * @param string $entityField
     */
    public function __construct($entityClass, $entityField)
    {
        $this->entityClass = $entityClass;
        $this->entityField = $entityField;
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

        $fieldRow = $this->connection->fetchAssoc($sql, [$this->entityClass, $this->entityField]);

        if ($fieldRow) {
            $this->executeQuery($logger, 'DELETE FROM oro_entity_config_field WHERE id = ?', [$fieldRow['id']]);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param $sql
     * @param array $parameters
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function executeQuery(LoggerInterface $logger, $sql, array $parameters = [])
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove config for field ' . $this->entityField . ' of entity' . $this->entityClass;
    }
}
