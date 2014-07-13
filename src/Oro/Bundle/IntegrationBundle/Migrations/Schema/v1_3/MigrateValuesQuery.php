<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_3;

use Psr\Log\LoggerInterface;

use Oro\Bundle\DataGridBundle\Common\Object as ConfigObject;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

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
        $this->logQuery($logger, $sql);
        $values = $this->connection->fetchAll($sql);

        $sql    = 'SELECT id FROM oro_organization WHERE name = :name';
        $params = ['name' => LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION];
        $types  = ['name' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);
        $organizationId = $this->connection->fetchColumn($sql, $params);

        $updateSql = 'UPDATE oro_integration_channel SET ' .
            'synchronization_settings = :syncSettings, ' .
            'mapping_settings = :mappingSettings, ' .
            'enabled = :enabled, ' .
            'organization_id = :organizationId' .
            'WHERE id = :id';

        foreach ($values as $row) {
            $params = [
                'syncSettings'    => ConfigObject::create(
                    [
                        'isTwoWaySyncEnabled' => $row['is_two_way_sync_enabled'],
                        'syncPriority'        => $row['sync_priority']
                    ]
                ),
                'mappingSettings' => ConfigObject::create([]),
                'enabled'         => 1,
                'organizationId'  => $organizationId,
                'id'              => $row['id']
            ];
            $types  = [
                'syncSettings'    => 'object',
                'mappingSettings' => 'object',
                'enabled'         => 'integer',
                'organizationId'  => 'integer',
                'id'              => 'integer'
            ];

            $this->logQuery($logger, $updateSql, $params, $types);
            $this->connection->executeUpdate($updateSql, $params, $types);
        }
    }
}
