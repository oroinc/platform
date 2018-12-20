<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_5;

use Doctrine\DBAL\Platforms\MySQL57Platform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Update field database type for json fields on mysql 5.7 to use native JSON
 */
class UpdateJsonArrayQuery extends ParametrizedMigrationQuery
{
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            'Convert a column with "DC2Type:json_array" type to "JSON" type on MySQL >= 5.7.8 and Doctrine 2.7'
        );
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
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof MySQL57Platform) {
            $updateSqls = [
                "ALTER TABLE oro_audit_field " .
                "CHANGE old_jsonarray old_jsonarray JSON DEFAULT NULL COMMENT '(DC2Type:json_array)'",
                "ALTER TABLE oro_audit_field " .
                "CHANGE new_jsonarray new_jsonarray JSON DEFAULT NULL COMMENT '(DC2Type:json_array)'",
                "ALTER TABLE oro_audit_field " .
                "CHANGE collection_diffs collection_diffs JSON DEFAULT NULL COMMENT '(DC2Type:json_array)'",
            ];

            foreach ($updateSqls as $updateSql) {
                $this->logQuery($logger, $updateSql);
                if (!$dryRun) {
                    $this->connection->executeUpdate($updateSql);
                }
            }
        }
    }
}
