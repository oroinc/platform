<?php

namespace Oro\Bundle\ReminderBundle\Migrations\Schema\v1_2_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddReminderSender implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_reminder');
        $table->addColumn('sender_id', 'integer', ['notnull' => false]);
        $table->addIndex(['sender_id'], 'idx_2f4f9f57f624b39d', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['sender_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
