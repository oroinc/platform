<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_9;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class FixCalendarsQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove corrupted and duplicates calendars';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        // remove calendars without owners
        $sql = 'DELETE FROM oro_calendar WHERE user_owner_id IS NULL';
        $this->logQuery($logger, $sql);
        $this->connection->executeQuery($sql);

        // find duplicated calendars
        $sql = 'SELECT * FROM oro_calendar ORDER BY id';
        $this->logQuery($logger, $sql);
        $calendars = $this->connection->fetchAll($sql);

        $existingCalendars = [];
        $calendarsForDeletion = [];
        foreach ($calendars as $calendar) {
            // skip calendars with empty organization (organization was removed)
            if (!$calendar['organization_id']) {
                continue;
            }

            $identifier = $calendar['user_owner_id'] . '_' . $calendar['organization_id'];
            if (in_array($identifier, $existingCalendars)) {
                $calendarsForDeletion[] = $calendar['id'];
            } else {
                $existingCalendars[] = $identifier;
            }
        }


        // remove calendars
        // as long as calendar events are also duplicated in all calendars there is no need to take care about them
        $types = ['id' => Connection::PARAM_INT_ARRAY];
        $batches = array_chunk($calendarsForDeletion, 1000);
        foreach ($batches as $batch) {
            $params = ['id' => $batch];
            $sql = 'DELETE FROM oro_calendar WHERE id IN (:id)';
            $this->logQuery($logger, $sql, $params, $types);
            $this->connection->executeQuery($sql, $params, $types);
        }
    }
}
