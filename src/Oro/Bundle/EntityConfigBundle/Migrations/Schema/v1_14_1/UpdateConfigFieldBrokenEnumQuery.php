<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_14_1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Fix enums that were set as active but actually were not created.
 * Set state from ACTIVE to DELETE for such enums.
 */
class UpdateConfigFieldBrokenEnumQuery extends ParametrizedMigrationQuery
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
        $fields = $this->loadEntitiesBrokenFields($logger);

        $entityIds = $this->fixEnums($fields, $logger, $dryRun);

        $this->updateEntityConfig($entityIds, $logger, $dryRun);
    }

    /**
     * @param array $fields
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @return array
     */
    protected function fixEnums(array $fields, LoggerInterface $logger, $dryRun = false): array
    {
        $query = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
        $types = ['data' => Type::TARRAY, 'id' => Type::INTEGER];

        $entityIds = [];
        foreach ($fields as $field) {
            $data = $field['data'];

            $data['extend']['state'] = ExtendScope::STATE_DELETE;
            $data['extend']['is_deleted'] = true;

            $params = ['data' => $data, 'id' => $field['id']];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params, $types);
            }

            $entityIds[] = $field['entity_id'];
        }

        return array_unique($entityIds);
    }

    /**
     * @param array $entityIds
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateEntityConfig(array $entityIds, LoggerInterface $logger, $dryRun = false)
    {
        $selectQuery = 'SELECT id, data FROM oro_entity_config WHERE id IN (:ids)';
        $selectParams = ['ids' => $entityIds];
        $selectTypes = ['ids' => Connection::PARAM_INT_ARRAY];
        $this->logQuery($logger, $selectQuery, $selectParams, $selectTypes);

        $updateQuery = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
        $updateTypes = ['data' => Type::TARRAY, 'id' => Type::INTEGER];

        foreach ($this->connection->fetchAll($selectQuery, $selectParams, $selectTypes) as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], Type::TARRAY);
            $data['extend']['upgradeable'] = true;

            $updateParams = ['data' => $data, 'id' => $row['id']];
            $this->logQuery($logger, $updateQuery, $updateParams, $updateTypes);
            if (!$dryRun) {
                $this->connection->executeUpdate($updateQuery, $updateParams, $updateTypes);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function loadEntitiesBrokenFields(LoggerInterface $logger): array
    {
        $sql = 'SELECT id, entity_id, data FROM oro_entity_config_field';
        $this->logQuery($logger, $sql);

        $result = [];
        foreach ($this->connection->fetchAll($sql) as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], Type::TARRAY);
            if ($this->isBrokenEnum($data)) {
                $row['data'] = $data;
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function isBrokenEnum(array $data): bool
    {
        return !empty($data['enum'])
            && empty($data['enum']['enum_code'])
            && !empty($data['extend']['state'])
            && $data['extend']['state'] === ExtendScope::STATE_ACTIVE;
    }
}
