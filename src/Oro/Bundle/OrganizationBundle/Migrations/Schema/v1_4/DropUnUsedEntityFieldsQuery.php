<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_4;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class DropUnUsedEntityFieldsQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Drop entity config values');
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
        $updateSql = <<<DQL
             DELETE FROM oro_entity_config_field WHERE field_name IN (?, ?)
             AND entity_id IN (SELECT id FROM oro_entity_config WHERE class_name = ?);
DQL;

        $params = [
            'currency',
            'precision',
            'Oro\\Bundle\\OrganizationBundle\\Entity\\Organization',
        ];

        $this->logQuery($logger, $updateSql, $params);
        if (!$dryRun) {
            $this->connection->executeUpdate($updateSql, $params);
        }
    }
}
