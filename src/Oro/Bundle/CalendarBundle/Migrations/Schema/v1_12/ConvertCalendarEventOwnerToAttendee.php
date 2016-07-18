<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_12;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class ConvertCalendarEventOwnerToAttendee extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Create attendees based on calendar events info and create relation between them.';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->createAttendees($logger);

        $this->updateCalendarEvents($logger);

        $this->updateAttendee($logger);
    }

    /**
     * This query inserts data into `oro_calendar_event_attendee` with related `oro_calendar_event id`,
     * we should use `ce.id`, because in `updateCalendarEvents` we should set `related_attendee`
     * and `calendar_event_id` will get  normal value in `updateAttendee`
     *
     * @param LoggerInterface $logger
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function createAttendees(LoggerInterface $logger)
    {
        $data = [
            'user_id' => 'c.user_owner_id',
            'calendar_event_id' => 'ce.id',
            'status_id' => 'NULL',
            'type_id' => 'NULL',
            'email' => 'u.email',
            'display_name' => 'CONCAT(u.first_name, \' \', u.last_name)',
            'created_at' => 'NOW()',
            'updated_at' => 'NOW()',
        ];

        if (in_array(
            'serialized_data',
            array_keys($this->connection->getSchemaManager()->listTableColumns('oro_calendar_event_attendee'))
        )
        ) {
            $data['serialized_data'] = '\'Tjs=\'';
        }

        $columns = implode(',', array_keys($data));
        $values = implode(',', $data);

        $sql = <<<EOD
INSERT INTO oro_calendar_event_attendee
    ($columns)
SELECT
    $values
FROM oro_calendar_event AS ce
    LEFT JOIN oro_calendar c ON ce.calendar_id = c.id
    LEFT JOIN oro_user u ON c.user_owner_id = u.id;
EOD;

        $this->logQuery($logger, $sql);
        $this->connection->executeQuery($sql);
    }

    /**
     * This query updates `oro_calendar_event` and sets `related_attendee` from `oro_calendar_event_attendee`
     *
     * @param LoggerInterface $logger
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateCalendarEvents(LoggerInterface $logger)
    {
        $sql = <<<EOD
UPDATE oro_calendar_event AS ce
SET related_attendee_id = (
    SELECT a.id
    FROM oro_calendar_event_attendee AS a
    WHERE a.calendar_event_id = ce.id
);
EOD;

        $this->logQuery($logger, $sql);
        $this->connection->executeQuery($sql);
    }

    /**
     * Update `oro_calendar_event_attendee` and set correct `calendar_event_id` using parent_id
     *
     * @param LoggerInterface $logger
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateAttendee(LoggerInterface $logger)
    {
        $sql = <<<EOD
UPDATE oro_calendar_event_attendee AS a
SET calendar_event_id = (
    SELECT CASE WHEN ce.parent_id IS NOT NULL THEN ce.parent_id ELSE ce.id END
    FROM oro_calendar_event AS ce
    WHERE a.calendar_event_id = ce.id
);
EOD;

        $this->logQuery($logger, $sql);
        $this->connection->executeQuery($sql);
    }
}
