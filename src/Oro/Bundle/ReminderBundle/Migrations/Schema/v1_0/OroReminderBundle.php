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
        $table->addColumn('subject', 'string', ['length' => 32]);
        $table->addColumn('start_at', 'datetime', []);
        $table->addColumn('expire_at', 'datetime', []);
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('interval_number', 'integer', []);
        $table->addColumn('interval_unit', 'string', ['length' => 1]);
        $table->addColumn('state', 'string', ['length' => 32]);
        $table->addColumn('related_entity_id', 'integer', []);
        $table->addColumn('related_entity_classname', 'string', ['length' => 255]);
        $table->addColumn('recipient_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->addColumn('sent_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['recipient_id'], 'IDX_2F4F9F57E92F8F78', []);
        $table->addIndex(['state'], 'reminder_state_idx', []);
        /** End of generate table oro_reminder **/

        /** Generate foreign keys for table oro_reminder **/
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['recipient_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_reminder **/
    }
}
