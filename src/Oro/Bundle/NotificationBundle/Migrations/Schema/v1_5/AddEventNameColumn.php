<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add event name columnt to email notification table and populate it with the data
 */
class AddEventNameColumn implements Migration, OrderedMigrationInterface
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_notification_email_notif');
        $table->addColumn('event_name', 'string', ['length' => 255, 'notnull' => false]);

        $queries->addPostQuery(new MigrateEventNamesQuery());
    }

    /**
     * @inheritDoc
     */
    public function getOrder()
    {
        return 1;
    }
}
