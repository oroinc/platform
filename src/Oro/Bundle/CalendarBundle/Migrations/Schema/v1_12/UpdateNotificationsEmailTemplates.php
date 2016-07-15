<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\ConnectionException;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateNotificationsEmailTemplates extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update date format filter in reminder and notification ' .
        'email templates using entity organization localization settings';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $dateFilterPattern = "|date('F j, Y, g:i A')";
        $calendarDateRangePattern[] = 'calendar_date_range(entity.start, entity.end, entity.allDay, 1)';
        $calendarDateRangePattern[] = "calendar_date_range(entity.start, entity.end, entity.allDay, 'F j, Y', 1)";
        $dateFilterReplacement = "|oro_format_datetime_organization({'organization': entity.calendar.organization})";
        $calendarDateRangeReplacement = 'calendar_date_range_organization(entity.start, entity.end,' .
            ' entity.allDay, 1, null, null, null, entity.calendar.organization)';

        $this->updateTemplates(
            $logger,
            'calendar_reminder',
            $dateFilterPattern,
            $dateFilterReplacement,
            $calendarDateRangePattern,
            $calendarDateRangeReplacement
        );
        $this->updateTemplates(
            $logger,
            'calendar_invitation_%',
            $dateFilterPattern,
            $dateFilterReplacement,
            $calendarDateRangePattern,
            $calendarDateRangeReplacement
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $templateName
     * @param string|array    $dateFilterPattern
     * @param string          $dateFilterReplacement
     * @param string|array    $calendarDateRangePattern
     * @param string          $calendarDateRangeReplacement
     *
     * @throws ConnectionException
     * @throws \Exception
     */
    protected function updateTemplates(
        LoggerInterface $logger,
        $templateName,
        $dateFilterPattern,
        $dateFilterReplacement,
        $calendarDateRangePattern,
        $calendarDateRangeReplacement
    ) {
        $sql = 'SELECT * FROM oro_email_template WHERE name LIKE :name ORDER BY id';
        $parameters = ['name' => $templateName];
        $types = ['name' => 'string'];

        $this->logQuery($logger, $sql, $parameters, $types);
        $templates = $this->connection->fetchAll($sql, $parameters, $types);

        try {
            $this->connection->beginTransaction();
            foreach ($templates as $template) {
                $subject = str_replace($dateFilterPattern, $dateFilterReplacement, $template['subject']);
                $content = str_replace($dateFilterPattern, $dateFilterReplacement, $template['content']);
                $content = str_replace($calendarDateRangePattern, $calendarDateRangeReplacement, $content);
                $this->connection->update(
                    'oro_email_template',
                    ['subject' => $subject, 'content' => $content],
                    ['id' => $template['id']]
                );
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
