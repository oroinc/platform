<?php

namespace Oro\Bundle\TrackingBundle\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class FillUniqueTrackingVisitsQuery extends ParametrizedMigrationQuery
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
        $truncateQuery = $this->connection->getDatabasePlatform()->getTruncateTableSQL('oro_tracking_unique_visit');
        $this->logQuery($logger, $truncateQuery);
        if (!$dryRun) {
            $this->connection->executeUpdate($truncateQuery);
        }

        $insertQuery = <<<'SQL'
INSERT INTO oro_tracking_unique_visit (user_identifier, action_date, website_id, visit_count)
  SELECT
    MD5(user_identifier),
    DATE(%1$s),
    website_id,
    COUNT(*)
  FROM oro_tracking_visit
  GROUP BY user_identifier, DATE(%1$s), website_id;
SQL;

        $insertQuery = sprintf($insertQuery, $this->getDateInUserTimezone($logger, 'first_action_time'));

        $this->logQuery($logger, $insertQuery);
        if (!$dryRun) {
            $this->connection->executeUpdate($insertQuery);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return null|string
     */
    protected function getTimezone(LoggerInterface $logger)
    {
        $query = <<<'SQL'
SELECT cv.text_value
FROM oro_config_value cv
JOIN oro_config c ON cv.config_id = c.id
WHERE cv.name = :name AND cv.section = :section AND c.entity = :entity
SQL;

        $params = [
            'entity' => 'app',
            'name' => 'timezone',
            'section' => 'oro_locale',
        ];
        $types = [
            'entity' => Type::STRING,
            'name' => Type::STRING,
            'section' => Type::STRING,
        ];

        $this->logQuery($logger, $query, $params, $types);

        $timezone = $this->connection->executeQuery($query, $params, $types)->fetchColumn();
        if (!$timezone) {
            $timezone = date_default_timezone_get();
        }

        return $timezone;
    }

    /**
     * @param LoggerInterface $logger
     * @param string $fieldName
     * @return string
     */
    private function getDateInUserTimezone(LoggerInterface $logger, $fieldName)
    {
        $timezone = $this->getTimezone($logger);
        if ($timezone) {
            if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
                return sprintf("CONVERT_TZ(%s, 'UTC', '%s')", $fieldName, $timezone);
            } else {
                return sprintf('"timestamp"(%s) AT TIME ZONE \'UTC\' AT TIME ZONE \'%s\'', $fieldName, $timezone);
            }
        }

        return $fieldName;
    }
}
