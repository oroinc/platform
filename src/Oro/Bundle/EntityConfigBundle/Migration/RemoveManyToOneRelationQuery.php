<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class RemoveManyToOneRelationQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var string
     */
    protected $associationName;

    /**
     * @param string $entityClass
     * @param string $associationName
     */
    public function __construct($entityClass, $associationName)
    {
        $this->entityClass = $entityClass;
        $this->associationName = $associationName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return "Remove association '{$this->associationName}' on '{$this->entityClass}'.";
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $targetClass = null;

        $sql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';

        $fieldRow = $this->connection->fetchAssoc($sql, [$this->entityClass, $this->associationName]);

        if (!$fieldRow) {
            $logger->info("Field '{$this->associationName}' not found in '{$this->entityClass}'");

            return;
        }

        $fieldData = $this->connection->convertToPHPValue($fieldRow['data'], Type::TARRAY);

        $this->executeQuery($logger, 'DELETE FROM oro_entity_config_field WHERE id = ?', [$fieldRow['id']]);

        $targetClass = $fieldData['extend']['target_entity'];

        $sql = 'SELECT e.data FROM oro_entity_config as e WHERE e.class_name = ? LIMIT 1';
        $entityRow = $this->connection->fetchAssoc($sql, [$this->entityClass]);
        $this->updateEntityData($logger, $targetClass, $entityRow['data']);
    }

    /**
     * @param LoggerInterface $logger
     * @param string $targetClass
     * @param string $data
     */
    protected function updateEntityData(LoggerInterface $logger, $targetClass, $data)
    {
        $data = $data ? $this->connection->convertToPHPValue($data, Type::TARRAY) : [];

        $extendKey = sprintf('manyToOne|%s|%s|%s', $this->entityClass, $targetClass, $this->associationName);
        if (isset($data['extend']['relation'][$extendKey])) {
            unset($data['extend']['relation'][$extendKey]);
        }
        if (isset($data['extend']['schema']['relation'][$this->associationName])) {
            unset($data['extend']['schema']['relation'][$this->associationName]);
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
     * @param string $sql
     * @param array $parameters
     * @throws DBALException
     */
    protected function executeQuery(LoggerInterface $logger, $sql, array $parameters = [])
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
