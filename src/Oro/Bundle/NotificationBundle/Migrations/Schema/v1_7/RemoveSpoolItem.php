<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes SpoolItem table.
 */
class RemoveSpoolItem implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->dropTable('oro_notification_email_spool');
    }
}
