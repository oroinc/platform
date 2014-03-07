<?php

namespace Oro\Bundle\ReminderBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroReminderBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroReminderTable($schema);
    }

    /**
     * Generate table oro_reminder
     *
     * @param Schema $schema
     */
    public static function oroReminderTable(Schema $schema)
    {
        /** Generate table oro_reminder **/
        $table = $schema->createTable('oro_reminder');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('subject', 'string', ['length' => 255]);
        $table->addColumn('due_date', 'datetime', []);
        $table->addColumn('reminder_interval', 'integer', []);
        $table->addColumn('state', 'object', ['comment' => '(DC2Type:object)']);
        $table->addColumn('related_entity_id', 'integer', []);
        $table->addColumn('related_entity_classname', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->addColumn('sent_at', 'datetime', []);
        $table->addColumn('is_sent', 'boolean', []);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_reminder **/
    }
}
