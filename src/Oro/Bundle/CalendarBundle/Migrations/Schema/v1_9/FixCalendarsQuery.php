<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_9;

use Psr\Log\LoggerInterface;

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
        $batches = array_chunk($calendarsForDeletion, 1000);
        foreach ($batches as $batch) {
            $sql = 'DELETE FROM oro_calendar WHERE id IN (' . implode(',', array_fill(0, count($batch), '?')) . ')';
            $this->logQuery($logger, $sql, $batch);
            $this->connection->executeQuery($sql, $batch);
        }
    }
}
