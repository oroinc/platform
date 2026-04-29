<?php

namespace Oro\Bundle\IntegrationBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Migrate webhook to integration bundle webhook
 */
class MigrateWebhookQuery extends ParametrizedMigrationQuery
{
    public function __construct(
        private readonly string $columnName,
        private readonly string $processorName
    ) {
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            'Migrate webhook to integration bundle webhook.'
        );
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $sql = sprintf('SELECT id, %1$s as access_token
            FROM oro_integration_transport 
            WHERE %1$s IS NOT NULL', $this->columnName);

        $now = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $insert = 'INSERT INTO oro_integration_webhook_consumer_settings
                (id, processor, enabled, created_at, updated_at) 
                VALUES (:id, :processor, :enabled, :created_at, :updated_at)';
        $preparedInsert = $this->connection->prepare($insert);

        foreach ($this->connection->executeQuery($sql)->fetchAllAssociative() as $row) {
            if ($dryRun) {
                $logger->info(sprintf('Migrate webhook with id "%s"', $row['access_token']));
                continue;
            }

            $preparedInsert->executeStatement([
                'id' => $row['access_token'],
                'processor' => $this->processorName,
                'enabled' => true,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        if (!$dryRun) {
            $this->connection->executeStatement(sprintf(
                'UPDATE oro_integration_transport 
                SET webhook_consumer_settings_id = %1$s 
                WHERE %1$s IS NOT NULL',
                $this->columnName
            ));
        }
    }
}
