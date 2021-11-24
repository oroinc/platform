<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds `additional_info` field to the `oro_notification_alert` table.
 */
class AddAdditionalInfoToNotificationAlertTable implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_notification_alert');
        $table->addColumn('additional_info', 'json', ['notnull' => false, 'comment' => '(DC2Type:json)']);
    }
}
