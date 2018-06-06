<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_14_2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Set is_deleted to false for fields is state Active and New.
 * Remove deleted fields from attribute families.
 */
class FixBrokenDeletedFieldsQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
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

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $fields = $this->getFields($logger);

        $deletedFields = array_filter($fields, function (array $field) {
            return !empty($field['data']['extend']['state'])
                && $field['data']['extend']['state'] === ExtendScope::STATE_DELETE;
        });

        if ($deletedFields) {
            $this->deleteRemovedAttributesFromFamily($deletedFields, $logger, $dryRun);
        }

        $brokenNonDeletedFields = array_filter($fields, function (array $field) {
            return !empty($field['data']['extend']['state'])
                && \in_array(
                    $field['data']['extend']['state'],
                    [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE],
                    true
                )
                && !empty($field['data']['extend']['is_deleted']);
        });
        if ($brokenNonDeletedFields) {
            $this->unsetDeletedFlag($brokenNonDeletedFields, $logger, $dryRun);
        }
    }

    /**
     * @param array $fields
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function deleteRemovedAttributesFromFamily(array $fields, LoggerInterface $logger, $dryRun = false)
    {
        $query = 'DELETE FROM oro_attribute_group_rel WHERE entity_config_field_id IN (:fieldIds)';
        $types = ['fieldIds' => Connection::PARAM_INT_ARRAY];
        $params = ['fieldIds' => array_column($fields, 'id')];
        $this->logQuery($logger, $query, $params, $types);

        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }

    /**
     * @param array $fields
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function unsetDeletedFlag(array $fields, LoggerInterface $logger, $dryRun = false)
    {
        $query = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
        $types = ['data' => Type::TARRAY, 'id' => Type::INTEGER];

        foreach ($fields as $field) {
            $data = $field['data'];
            $data['extend']['is_deleted'] = false;

            $updateParams = ['data' => $data, 'id' => $field['id']];
            $this->logQuery($logger, $query, $updateParams, $types);

            if (!$dryRun) {
                $this->connection->executeUpdate($query, $updateParams, $types);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getFields(LoggerInterface $logger): array
    {
        $sql = 'SELECT id, data FROM oro_entity_config_field';
        $this->logQuery($logger, $sql);

        $result = [];
        foreach ($this->connection->fetchAll($sql) as $field) {
            $data = $this->connection->convertToPHPValue($field['data'], Type::TARRAY);
            $field['data'] = $data;
            $result[] = $field;
        }

        return $result;
    }
}
