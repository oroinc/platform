<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_3;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Component\Config\Common\ConfigObject;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

class MigrateValuesQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Move integration settings from flat separated fields to serialized single field');
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
     * {@inheritdoc}
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $values         = $this->getOldValues($logger);
        $organizationId = $this->getDefaultOrganizationId($logger);

        $updateSql = 'UPDATE oro_integration_channel SET ' .
            'synchronization_settings = :syncSettings, ' .
            'mapping_settings = :mappingSettings, ' .
            'enabled = :enabled ' . ($organizationId ? ', organization_id = :organizationId ' : '') .
            'WHERE id = :id';
        foreach ($values as $row) {
            $params = [
                'syncSettings'    => ConfigObject::create(
                    [
                        'isTwoWaySyncEnabled' => (bool)$row['is_two_way_sync_enabled'],
                        'syncPriority'        => $row['sync_priority']
                    ]
                ),
                'mappingSettings' => ConfigObject::create([]),
                'enabled'         => 1,
                'id'              => $row['id']
            ];
            $types  = [
                'syncSettings'    => 'object',
                'mappingSettings' => 'object',
                'enabled'         => 'integer',
                'id'              => 'integer'
            ];

            if ($organizationId) {
                $params['organizationId'] = $organizationId;
                $types['organizationId']  = 'integer';
            }

            $this->logQuery($logger, $updateSql, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($updateSql, $params, $types);
            }
        }
    }

    /**
     * Read values to migrate from old database structure
     *
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getOldValues(LoggerInterface $logger)
    {
        $sql = 'SELECT id, is_two_way_sync_enabled, sync_priority FROM oro_integration_channel';
        $this->logQuery($logger, $sql);

        return $this->connection->fetchAll($sql);
    }

    /**
     * Fetch default organization ID or if not found fallback on first existing one
     *
     * @param LoggerInterface $logger
     *
     * @return false|integer
     */
    protected function getDefaultOrganizationId(LoggerInterface $logger)
    {
        $sql    = 'SELECT id FROM oro_organization WHERE name = :name';
        $params = ['name' => LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION];
        $types  = ['name' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);
        $organizationId = $this->connection->fetchColumn($sql, $params);

        if (false === $organizationId) {
            $sql = 'SELECT id FROM oro_organization';
            $this->logQuery($logger, $sql);
            $organizationIds = array_map(
                function ($row) {
                    return $row['id'];
                },
                $this->connection->fetchAll($sql)
            );

            if (!empty($organizationIds)) {
                $organizationId = min($organizationIds);
            }
        }

        return $organizationId;
    }
}
