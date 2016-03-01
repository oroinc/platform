<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

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
    public function execute(LoggerInterface $logger)
    {
        $enumClass = null;

        $sql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';

        $fieldRow = $this->connection->fetchAssoc($sql, [$this->entityClass, $this->enumField]);

        if ($fieldRow) {
            $enumClass = $this->deleteEnumData($logger, $fieldRow['id'], $fieldRow['data']);
        }

        if ($enumClass) {
            $sql = 'SELECT e.data FROM oro_entity_config as e WHERE e.class_name = ? LIMIT 1';
            $entityRow = $this->connection->fetchAssoc($sql, [$this->entityClass]);
            if ($entityRow) {
                $this->updateEntityData($logger, $enumClass, $entityRow['data']);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string $id
     * @param string $data
     * @return null|string
     */
    protected function deleteEnumData(LoggerInterface $logger, $id, $data)
    {
        $enumClass = null;

        $data = $data ? $this->connection->convertToPHPValue($data, Type::TARRAY) : [];

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
     * @param LoggerInterface $logger
     * @param string $enumClass
     * @param string $data
     */
    protected function updateEntityData(LoggerInterface $logger, $enumClass, $data)
    {
        $data = $data ? $this->connection->convertToPHPValue($data, Type::TARRAY) : [];

        $extendKey = sprintf('manyToOne|%s|%s|%s', $this->entityClass, $enumClass, $this->enumField);
        if (isset($data['extend']['relation'][$extendKey])) {
            unset($data['extend']['relation'][$extendKey]);
        }
        if (isset($data['extend']['schema']['relation'][$this->enumField])) {
            unset($data['extend']['schema']['relation'][$this->enumField]);
        }

        $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

        $this->executeQuery(
            $logger,
            'UPDATE oro_entity_config SET data = ? WHERE class_name = ?',
            [$data, $this->entityClass]
        );
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
        return 'Remove outdated '. $this->enumField .' enum field data';
    }
}
