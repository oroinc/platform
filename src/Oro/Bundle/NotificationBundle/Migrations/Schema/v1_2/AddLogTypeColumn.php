<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddLogTypeColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_notification_email_spool');
        $table->addColumn('log_type', 'string', ['notnull' => false, 'length' => 255]);
    }
}
