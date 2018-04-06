<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveFieldQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    protected $entityClass;

    /** @var string */
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
    public function getDescription()
    {
        return 'Remove config for field ' . $this->entityField . ' of entity' . $this->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $fieldRow = $this->getFieldRow($this->entityClass, $this->entityField);
        if (!$fieldRow) {
            $logger->info("Field '{$this->entityField}' not found in '{$this->entityClass}'");

            return;
        }

        $this->removeFieldConfig($logger, $fieldRow['id']);
    }

    /**
     * @param string $entityClass
     * @param string $entityField
     *
     * @return array
     */
    protected function getFieldRow($entityClass, $entityField)
    {
        $getFieldSql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';

        return $this->connection->fetchAssoc(
            $getFieldSql,
            [
                $entityClass,
                $entityField
            ]
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param integer         $fieldRowId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeFieldConfig(LoggerInterface $logger, $fieldRowId)
    {
        $this->executeQuery(
            $logger,
            'DELETE FROM oro_entity_config_field WHERE id = ?',
            [
                $fieldRowId
            ]
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param                 $sql
     * @param array           $parameters
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function executeQuery(LoggerInterface $logger, $sql, array $parameters = [])
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
