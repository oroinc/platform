<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class PreserveTrackingConfigQuery extends ParametrizedMigrationQuery
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
        if (!$this->isConfigDefault($logger)) {
            return;
        }

        $query = $this->createPreserveQuery();
        if ($dryRun) {
            $logs = $query->getDescription();
            foreach ($logs as $log) {
                $logger->info($log);
            }
        } else {
            $query->execute($logger);
        }
    }

    /**
     * @return bool
     */
    protected function isConfigDefault(LoggerInterface $logger)
    {
        $query = <<<'SQL'
SELECT COUNT(1)
FROM oro_config_value cv
JOIN oro_config c ON cv.config_id = c.id
WHERE cv.name = :name AND cv.section = :section AND c.entity = :entity
SQL;

        $params = [
            'entity' => 'app',
            'name' => 'dynamic_tracking_enabled',
            'section' => 'oro_tracking',
        ];

        $this->logQuery($logger, $query, $params);

        return !$this->connection->executeQuery($query, $params)->fetchColumn();
    }

    /**
     * @return ParametrizedSqlMigrationQuery
     */
    public function createPreserveQuery()
    {
        $query = new ParametrizedSqlMigrationQuery(
            $sql = <<<'SQL'
INSERT INTO oro_config_value
    (config_id, name, section, text_value, object_value, array_value, type, created_at, updated_at)
SELECT
    c.id,
    :name,
    :section,
    :text_value,
    :object_value,
    :array_value,
    :type,
    :created_at,
    :created_at
FROM oro_config c
WHERE c.entity = :entity
SQL
            ,
            [
                'entity' => 'app',
                'name' => 'dynamic_tracking_enabled',
                'section' => 'oro_tracking',
                'text_value' => '1',
                'object_value' => null,
                'array_value' => null,
                'type' => 'scalar',
                'created_at' => (new \DateTime())->setTimezone(new \DateTimeZone('UTC')),
            ],
            [
                'entity' => Type::STRING,
                'name' => Type::STRING,
                'section' => Type::STRING,
                'text_value' => Type::TEXT,
                'object_value' => Type::OBJECT,
                'array_value' => Type::TARRAY,
                'type' => Type::STRING,
                'created_at' => Type::DATETIME,
            ]
        );

        $query->setConnection($this->connection);

        return $query;
    }
}
