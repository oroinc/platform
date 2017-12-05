<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNotificationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addEntityEmailsField($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addEntityEmailsField(Schema $schema)
    {
        $table = $schema->getTable('oro_notification_recip_list');
        $table->addColumn(
            'entity_emails',
            'simple_array',
            [
                'comment' => '(DC2Type:simple_array)',
                'notnull' => false
            ]
        );
    }
}
