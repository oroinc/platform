<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Remove outdated enum field data.
 */
class RemoveEnumFieldQuery extends ParametrizedMigrationQuery
{
    /** @var string  */
    protected $entityClass = '';

    /** @var string  */
    protected $enumField = '';

    /**
     * @param string $entityClass
     * @param string $enumField
     */
    public function __construct($entityClass, $enumField)
    {
        $this->entityClass = $entityClass;
        $this->enumField = $enumField;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $sql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';

        $fieldRow = $this->connection->fetchAssoc($sql, [$this->entityClass, $this->enumField]);

        if (!$fieldRow) {
            $logger->info("Enum field '{$this->enumField}' from Entity '{$this->entityClass}' is not found");

            return;
        }

        $enumClass = $this->deleteEnumData($logger, $fieldRow['id'], $fieldRow['data']);

        if ($enumClass) {
            $sql = 'SELECT e.data FROM oro_entity_config as e WHERE e.class_name = ? LIMIT 1';
            $entityRow = $this->connection->fetchAssoc($sql, [$this->entityClass]);
            if ($entityRow) {
                $this->updateEntityData($logger, $enumClass, $entityRow['data']);
            }
        }
    }

    protected function deleteEnumData(LoggerInterface $logger, string $id, string $data): ?string
    {
        $enumClass = null;

        $data = $data ? $this->connection->convertToPHPValue($data, Types::ARRAY) : [];

        // delete field data
        $this->executeQuery($logger, 'DELETE FROM oro_entity_config_field WHERE id = ?', [$id]);

        // remove enum entity data
        if (!empty($data['extend']['target_entity'])) {
            $enumClass = $data['extend']['target_entity'];

            $sql = 'SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1';
            $enumRow = $this->connection->fetchAssoc($sql, [$enumClass]);

            if ($enumRow) {
                $enumId = $enumRow['id'];

                // delete enum fields data
                $this->executeQuery($logger, 'DELETE FROM oro_entity_config_field WHERE entity_id = ?', [$enumId]);

                // delete enum entity data
                $this->executeQuery($logger, 'DELETE FROM oro_entity_config WHERE class_name = ?', [$enumClass]);
            }
        }

        return $enumClass;
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    protected function updateEntityData(LoggerInterface $logger, string $enumClass, string $data)
    {
        $data = $data ? $this->connection->convertToPHPValue($data, Types::ARRAY) : [];

        $extendKey = sprintf('manyToOne|%s|%s|%s', $this->entityClass, $enumClass, $this->enumField);
        if (isset($data['extend']['relation'][$extendKey])) {
            unset($data['extend']['relation'][$extendKey]);
        }
        // for Multi-Enum field type.
        $extendKey = sprintf('manyToMany|%s|%s|%s', $this->entityClass, $enumClass, $this->enumField);
        if (isset($data['extend']['relation'][$extendKey])) {
            unset($data['extend']['relation'][$extendKey]);
        }
        if (isset($data['extend']['schema']['relation'][$this->enumField])) {
            unset($data['extend']['schema']['relation'][$this->enumField]);
        }

        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $this->executeQuery(
            $logger,
            'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
            [$data, $this->entityClass]
        );
    }

    /**
     * @throws DBALException|Exception
     */
    protected function executeQuery(LoggerInterface $logger, $sql, array $parameters = []): void
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Remove outdated '. $this->enumField .' enum field data';
    }
}
