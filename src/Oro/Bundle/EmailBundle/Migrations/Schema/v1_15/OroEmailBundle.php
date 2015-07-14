<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroEmailMailboxTable($schema);
        self::createOroEmailMailboxProcessorTable($schema);
        self::addOwnerMailboxColumn($schema);
        self::addOroEmailMailboxForeignKeys($schema);
    }

    public static function createOroEmailMailboxTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('processor_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('smtp_settings', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['processor_id'], 'UNIQ_574C364F37BAC19A');
        $table->addUniqueIndex(['origin_id'], 'UNIQ_574C364F56A273CC');
    }

    public static function createOroEmailMailboxProcessorTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_mailbox_processor');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
    }

    private static function addOwnerMailboxColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_email_address');

        $table->addColumn('owner_mailbox_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_mailbox_id'], 'IDX_FC9DBBC53486AC89');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_mailbox'),
            ['owner_mailbox_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null],
            'FK_FC9DBBC53486AC89');
    }

    /**
     * Add oro_email_mailbox foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroEmailMailboxForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_email_mailbox');
        $table->addForeignKeyConstraint($schema->getTable('oro_email_mailbox_processor'),
            ['processor_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]);
    }
}
