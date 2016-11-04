<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestFrameworkBundle implements Migration, ActivityExtensionAwareInterface
{
    const CALENDAR_EVENT_TABLE = 'oro_calendar_event';
    const TEST_ACTIVITY_TABLE = 'test_activity_target';

    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addTestActivityToCalendarEvent($schema, $this->activityExtension);
    }

    /**
     * Add test activity to calendar event, if installing or migrating in test environment
     *
     * @param Schema            $schema
     * @param ActivityExtension $activityExtension
     */
    public static function addTestActivityToCalendarEvent(Schema $schema, ActivityExtension $activityExtension)
    {
        if ($schema->hasTable(self::TEST_ACTIVITY_TABLE)) {
            $activityTableName = $activityExtension->getAssociationTableName(
                self::CALENDAR_EVENT_TABLE,
                self::TEST_ACTIVITY_TABLE
            );
            if (!$schema->hasTable($activityTableName)) {
                $activityExtension->addActivityAssociation(
                    $schema,
                    self::CALENDAR_EVENT_TABLE,
                    self::TEST_ACTIVITY_TABLE,
                    true
                );
            }
        }
    }
}
