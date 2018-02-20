<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Query;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

abstract class AbstractEntityConfigQuery extends ParametrizedMigrationQuery
{
    /**
     * @return int
     */
    abstract public function getRowBatchLimit();

    /**
     * @param array           $row
     * @param LoggerInterface $logger
     */
    abstract public function processRow(array $row, LoggerInterface $logger);

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getEntityConfigCount() / $this->getRowBatchLimit());

        $entityConfigQb = $this->createEntityConfigQb()
            ->setMaxResults($this->getRowBatchLimit());

        for ($i = 0; $i < $steps; $i++) {
            $rows = $entityConfigQb
                ->setFirstResult($i * $this->getRowBatchLimit())
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $this->processRow($row, $logger);
            }
        }
    }

    /**
     * @param int    $entityId
     * @param string $fieldName
     *
     * @return array
     */
    protected function getEntityConfigFieldFromDb($entityId, $fieldName)
    {
        $fieldConfigFromDb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('oro_entity_config_field', 'ecf')
            ->where('ecf.entity_id = :entity_id')
            ->andWhere('ecf.field_name = :field_name')
            ->setParameter('entity_id', $entityId)
            ->setParameter('field_name', $fieldName)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        return $fieldConfigFromDb;
    }

    /**
     * @param string $entityClassName
     * @return array
     */
    protected function getEntityConfigFromDb($entityClassName)
    {
        return $this->createEntityConfigQb()
            ->where('ec.class_name = :entity_class_name')
            ->setParameter('entity_class_name', $entityClassName)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array                $entityData
     * @param int                  $id
     * @param LoggerInterface|null $logger
     */
    protected function updateEntityConfigData(array $entityData, $id, LoggerInterface $logger = null)
    {
        $query = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$this->connection->convertToDatabaseValue($entityData, Type::TARRAY), $id];

        if ($logger) {
            $this->logQuery($logger, $query, $parameters);
        }
        $this->connection->executeUpdate($query, $parameters);
    }

    /**
     * @return int
     */
    protected function getEntityConfigCount()
    {
        return $this->createEntityConfigQb()
            ->select('COUNT(1)')
            ->execute()
            ->fetchColumn();
    }

    /**
     * @return QueryBuilder
     */
    protected function createEntityConfigQb()
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('oro_entity_config', 'ec');
    }
}
