<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Increase `operation` and `step` columns length for `oro_notification_alert` table
 */
class IncreaseNotificationAlertColumnsLength implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_notification_alert');
        if ($table->getColumn('operation')->getLength() < 50) {
            $table->changeColumn('operation', ['length' => 50]);
        }
        if ($table->getColumn('step')->getLength() < 50) {
            $table->changeColumn('step', ['length' => 50]);
        }
    }
}
