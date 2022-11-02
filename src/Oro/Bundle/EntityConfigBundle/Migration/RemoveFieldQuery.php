<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Query that helps to entirely remove field from class metadata.
 */
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
        $logger = new ArrayLogger();
        $logger->info(
            'Remove config for field ' . $this->entityField . ' of entity' . $this->entityClass
        );
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    protected function doExecute(LoggerInterface $logger, bool $dryRun = false)
    {
        $fieldRow = $this->getFieldRow($this->entityClass, $this->entityField);
        if (!$fieldRow) {
            $logger->info("Field '{$this->entityField}' not found in '{$this->entityClass}'");

            return;
        }

        $this->removeFieldConfig($logger, $fieldRow['id'], $dryRun);
        $this->updateClassConfig($logger, $dryRun);
    }

    /**
     * @param string $entityClass
     * @param string $entityField
     *
     * @return array
     */
    protected function getFieldRow(string $entityClass, string $entityField)
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
     * @param integer $fieldRowId
     * @param bool $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeFieldConfig(LoggerInterface $logger, $fieldRowId, bool $dryRun = false)
    {
        $this->executeQuery(
            $logger,
            'DELETE FROM oro_entity_config_field WHERE id = ?',
            [
                $fieldRowId
            ],
            $dryRun
        );
    }

    protected function executeQuery(LoggerInterface $logger, string $sql, array $parameters = [], bool $dryRun = false)
    {
        $this->logQuery($logger, $sql, $parameters);
        if (!$dryRun) {
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
        }
    }

    protected function updateClassConfig(LoggerInterface $logger, bool $dryRun = false)
    {
        $sql = 'SELECT e.data FROM oro_entity_config as e WHERE e.class_name = ? LIMIT 1';
        $row = $this->connection->fetchAssoc($sql, [$this->entityClass]);
        if ($row) {
            $data = $this->connection->convertToPHPValue($row['data'], Types::ARRAY);
            if (isset($data['extend']['schema']['property'][$this->entityField])) {
                unset($data['extend']['schema']['property'][$this->entityField]);
            }

            if (isset($data['extend']['schema'])) {
                $entityName = $data['extend']['schema']['entity'];
                if (isset($data['extend']['schema']['doctrine'][$entityName]['fields'][$this->entityField])) {
                    unset($data['extend']['schema']['doctrine'][$entityName]['fields'][$this->entityField]);
                }
            }

            if (isset($data['extend']['index'][$this->entityField])) {
                unset($data['extend']['index'][$this->entityField]);
            }

            $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);
            $this->executeQuery(
                $logger,
                'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
                [$data, $this->entityClass],
                $dryRun
            );
        }
    }
}
