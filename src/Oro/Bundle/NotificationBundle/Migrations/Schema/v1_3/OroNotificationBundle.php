<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_3;

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
        $this->addAdditionalEmailAssociationsField($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addAdditionalEmailAssociationsField(Schema $schema)
    {
        $table = $schema->getTable('oro_notification_recip_list');
        $table->addColumn(
            'additional_email_associations',
            'simple_array',
            [
                'comment' => '(DC2Type:simple_array)',
                'notnull' => false
            ]
        );
    }
}
