<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_10;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateConfigFieldDatagridScopeQuery extends ParametrizedMigrationQuery
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
     * @param bool            $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $fields = $this->loadEntitiesFields($logger);

        $this->fixDatagridScope($fields, $logger, $dryRun);
    }

    /**
     * @param array           $fields
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     *
     * @return array
     */
    protected function fixDatagridScope(array $fields, LoggerInterface $logger, $dryRun = false)
    {
        $query = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
        $types = ['data' => 'array', 'id' => 'integer'];

        foreach ($fields as $field) {
            $data = $field['data'];
            if (isset($data['datagrid']['is_visible']) && is_bool($data['datagrid']['is_visible'])) {
                $data['datagrid']['is_visible'] = (int)$data['datagrid']['is_visible'];
                $params                         = ['data' => $data, 'id' => $field['id']];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function loadEntitiesFields(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config_field';
        $this->logQuery($logger, $sql);

        $rows = $this->connection->fetchAll($sql);
        foreach ($rows as $key => $row) {
            $rows[$key]['data'] = $this->connection->convertToPHPValue($row['data'], 'array');
        }

        return $rows;
    }
}
