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

        // find and remove duplicated calendars
        // as long as calendar events are also duplicated in all calendars there is no need to take care about them
        $sql = 'SELECT * FROM oro_calendar ORDER BY id';
        $this->logQuery($logger, $sql);
        $calendars = $this->connection->fetchAll($sql);

        $existingCalendars = [];
        foreach ($calendars as $calendar) {
            // skip system calendars
            if (!$calendar['organization_id']) {
                continue;
            }

            $identifier = $calendar['user_owner_id'] . '_' . $calendar['organization_id'];
            if (in_array($identifier, $existingCalendars)) {
                $sql = 'DELETE FROM oro_calendar WHERE id = ?';
                $parameters = [$calendar['id']];
                $this->logQuery($logger, $sql, $parameters);
                $this->connection->executeQuery($sql, $parameters);
            } else {
                $existingCalendars[] = $identifier;
            }
        }
    }
}
