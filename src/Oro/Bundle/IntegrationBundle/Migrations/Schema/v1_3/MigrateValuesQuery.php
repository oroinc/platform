<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_3;

use Psr\Log\LoggerInterface;

use Oro\Bundle\DataGridBundle\Common\Object as ConfigObject;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class MigrateValuesQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Move integration settings from flat separated fields to serialized single field';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'SELECT id, is_two_way_sync_enabled, sync_priority FROM oro_integration_channel';
        $logger->notice($sql);

        $values = $this->connection->fetchAll($sql);
        foreach ($values as $row) {
            $sql = 'UPDATE oro_integration_channel ' .
                'SET synchronization_settings = :syncSettings, mapping_settings = :mappingSettings ' .
                'WHERE id = :id';

            $params = [
                'syncSettings'    => ConfigObject::create(
                    [
                        'isTwoWaySyncEnabled' => $row['is_two_way_sync_enabled'],
                        'syncPriority'        => $row['sync_priority']
                    ]
                ),
                'mappingSettings' => ConfigObject::create([]),
                'id'              => $row['id']
            ];
            $types  = ['syncSettings' => 'object', 'mappingSettings' => 'object', 'id' => 'integer'];

            $this->logQuery($logger, $sql, $params, $types);
            $this->connection->executeUpdate($sql, $params, $types);
        }
    }
}
