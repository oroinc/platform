<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveTableQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Remove event table with all the indexes
 */
class DropEventTable implements Migration, OrderedMigrationInterface
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->dropEventTable($schema, $queries);
    }

    private function dropEventTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_notification_email_notif');

        // drop index by event_id
        if ($table->hasIndex('IDX_A3D00FDF71F7E88B')) {
            $table->dropIndex('IDX_A3D00FDF71F7E88B');
        }

        // drop foreign key to event table
        foreach ($table->getForeignKeys() as $foreignKeyConstraint) {
            if (in_array('event_id', $foreignKeyConstraint->getLocalColumns(), true)) {
                $table->removeForeignKey($foreignKeyConstraint->getName());
            }
        }

        // drop event table and add event_name column
        if ($schema->hasTable('oro_notification_event')) {
            $table->dropColumn('event_id');
            $schema->dropTable('oro_notification_event');
            $queries->addPostQuery(new RemoveTableQuery('Oro\Bundle\NotificationBundle\Entity\Event'));
            $queries->addPostQuery(new RemoveFieldQuery(
                'Oro\Bundle\NotificationBundle\Entity\EmailNotification',
                'event'
            ));
        }
    }

    /**
     * @inheritDoc
     */
    public function getOrder()
    {
        return 2;
    }
}
