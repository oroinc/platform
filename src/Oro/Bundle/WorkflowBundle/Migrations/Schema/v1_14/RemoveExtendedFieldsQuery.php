<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class RemoveExtendedFieldsQuery extends ParametrizedMigrationQuery
{
    const PROPERTY_WORKFLOW_ITEM = 'workflowItem';
    const PROPERTY_WORKFLOW_STEP = 'workflowStep';

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateConfigs($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->migrateConfigs($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function migrateConfigs(LoggerInterface $logger, $dryRun = false)
    {
        $queries = [];

        // prepare update queries
        $rows = $this->getRows($logger);
        foreach ($rows as $row) {
            $workflowItemData = $this->connection->convertToPHPValue($row['wi_data'], 'array');
            if ($this->isApplicable($workflowItemData)) {
                $queries[] = $this->getUpdateQuery($workflowItemData, $row['wi_id']);
            }

            $workflowStepData = $this->connection->convertToPHPValue($row['ws_data'], 'array');
            if ($this->isApplicable($workflowStepData)) {
                $queries[] = $this->getUpdateQuery($workflowStepData, $row['ws_id']);
            }
        }

        // execute update queries
        foreach ($queries as $val) {
            $this->logQuery($logger, $val[0], $val[1], $val[2]);
            if (!$dryRun) {
                $this->connection->executeUpdate($val[0], $val[1], $val[2]);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getRows(LoggerInterface $logger)
    {
        $query  = 'SELECT cfi.id AS wi_id, cfs.id AS ws_id, c.class_name, cfi.data AS wi_data, cfs.data AS ws_data ' .
            'FROM oro_entity_config c ' .
            'INNER JOIN oro_entity_config_field cfi ON cfi.entity_id = c.id ' .
            'INNER JOIN oro_entity_config_field cfs ON cfs.entity_id = c.id ' .
            'WHERE cfi.field_name = :wi and cfs.field_name = :ws';
        $params = ['wi' => self::PROPERTY_WORKFLOW_ITEM, 'ws' => self::PROPERTY_WORKFLOW_STEP];
        $types  = ['field' => Type::STRING];

        $this->logQuery($logger, $query, $params, $types);

        // prepare update queries
        $rows = $this->connection->fetchAll($query, $params, $types);

        return array_filter(
            $rows,
            function ($row) {
                return strpos($row['class_name'], 'Extend\\Entity\\') !== 0;
            }
        );
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function isApplicable(array $data)
    {
        return isset($data['extend']['owner']) && $data['extend']['owner'] === ExtendScope::OWNER_CUSTOM &&
            isset($data['extend']['state']) && $data['extend']['state'] !== ExtendScope::STATE_DELETE;
    }

    /**
     * @param array $data
     * @param int $id
     * @return array
     */
    protected function getUpdateQuery(array $data, $id)
    {
        $data['extend']['state'] = ExtendScope::STATE_DELETE;
        
        return [
            'UPDATE oro_entity_config_field SET data = :data WHERE id = :id',
            ['data' => $data, 'id' => $id],
            ['data' => Type::TARRAY, 'id' => Type::INTEGER]
        ];
    }
}
