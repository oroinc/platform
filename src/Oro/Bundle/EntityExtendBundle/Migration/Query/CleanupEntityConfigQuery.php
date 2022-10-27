<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

/**
 * This query allows cleaning up entity and field configurations by given 'scope-to-property' list.
 */
class CleanupEntityConfigQuery extends AbstractEntityConfigQuery
{
    const LIMIT = 100;

    private const ENTITY_INDEX_TYPE = 0;
    private const FIELD_INDEX_TYPE = 1;

    private bool $hasFieldConfigChanges;

    private bool $hasEntityConfigChanges;

    private array $deprecatedEntityConfigs = [];

    private array $deprecatedFieldConfigs = [];

    public function __construct(array $deprecatedEntityConfigs, array $deprecatedFieldConfigs)
    {
        $this->deprecatedEntityConfigs = $deprecatedEntityConfigs;
        $this->deprecatedFieldConfigs = $deprecatedFieldConfigs;
    }

    /**
     * {@inheritDoc}
     */
    public function getRowBatchLimit()
    {
        return self::LIMIT;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return ["Allows to clean up entity and field configurations by given 'scope-to-property' list"];
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->hasEntityConfigChanges = $this->hasFieldConfigChanges = false;
        parent::execute($logger);

        $steps = ceil($this->getFieldConfigsCount() / $this->getRowBatchLimit());

        $fieldsConfigQb = $this->createFieldsConfigQb()
            ->setMaxResults($this->getRowBatchLimit());

        for ($i = 0; $i < $steps; $i++) {
            $rows = $fieldsConfigQb
                ->setFirstResult($i * $this->getRowBatchLimit())
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $this->processFieldRow($row, $logger);
            }
        }

        if ($this->hasEntityConfigChanges) {
            $this->cleanupIndicesRecords($this->deprecatedEntityConfigs, self::ENTITY_INDEX_TYPE);
        }
        if ($this->hasFieldConfigChanges) {
            $this->cleanupIndicesRecords($this->deprecatedFieldConfigs, self::FIELD_INDEX_TYPE);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function processRow(array $row, LoggerInterface $logger)
    {
        $entityConfigData = $this->connection->convertToPHPValue($row['data'], 'array');
        $entityConfigId = (int)$row['id'];

        if ($this->cleanupConfigData($entityConfigData, $this->deprecatedEntityConfigs)) {
            $this->updateEntityConfigData($entityConfigData, $entityConfigId, $logger);
            $this->hasEntityConfigChanges = true;
        }
    }

    /**
     * @param array $row
     * @param LoggerInterface $logger
     */
    private function processFieldRow(array $row, LoggerInterface $logger)
    {
        $fieldConfigData = $this->connection->convertToPHPValue($row['data'], 'array');
        $fieldConfigId = (int)$row['id'];

        if ($this->cleanupConfigData($fieldConfigData, $this->deprecatedFieldConfigs)) {
            $this->updateFieldConfigData($fieldConfigData, $fieldConfigId, $logger);
            $this->hasFieldConfigChanges = true;
        }
    }

    private function cleanupConfigData(array &$configData, array $deprecatedConfigs): bool
    {
        $hasChanges = false;

        foreach ($deprecatedConfigs as $scope => $fields) {
            if (!isset($configData[$scope])) {
                continue;
            }

            if ($fields !== null) {
                foreach ($fields as $field) {
                    if (isset($configData[$scope][$field])) {
                        unset($configData[$scope][$field]);
                        $hasChanges = true;
                    }
                }
            } else {
                if (isset($configData[$scope])) {
                    unset($configData[$scope]);
                    $hasChanges = true;
                }
            }
        }

        return $hasChanges;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function cleanupIndicesRecords(array $deprecatedConfigs, int $indexType)
    {
        foreach ($deprecatedConfigs as $scope => $fields) {
            $qb = $this->connection->createQueryBuilder();
            $qb->delete('oro_entity_config_index_value')
                ->andWhere('scope = :scope')
                ->setParameter('scope', $scope);

            if ($indexType == self::ENTITY_INDEX_TYPE) {
                $qb->andWhere('field_id IS NULL');
            } elseif ($indexType == self::FIELD_INDEX_TYPE) {
                $qb->andWhere('entity_id IS NULL');
            } else {
                throw new \UnexpectedValueException('Unknown entity configuration index type is given');
            }

            if ($fields !== null) {
                $qb->andWhere('code IN(:codes)')
                    ->setParameter('codes', $fields, Connection::PARAM_STR_ARRAY);
            }

            $qb->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntityConfigQb()
    {
        $qb = parent::createEntityConfigQb();
        $qb->addOrderBy('ec.id');

        return $qb;
    }

    protected function getEntityConfigCount(): int
    {
        return $this->createEntityConfigQb()
            ->select('COUNT(1)')
            ->resetQueryParts(['orderBy'])
            ->execute()
            ->fetchColumn();
    }

    private function createFieldsConfigQb(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('oro_entity_config_field', 'ecf')
            ->addOrderBy('ecf.id');
    }

    private function getFieldConfigsCount(): int
    {
        return (int)$this->createFieldsConfigQb()
            ->select('COUNT(1)')
            ->resetQueryParts(['orderBy'])
            ->execute()
            ->fetchColumn();
    }
}
