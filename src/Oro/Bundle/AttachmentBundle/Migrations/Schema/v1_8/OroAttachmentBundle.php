<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAttachmentBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroAttachmentFileItemTable($schema);

        /** Foreign keys generation **/
        $this->addOroAttachmentFileItemForeignKeys($schema);
    }

    /**
     * Create oro_attachment_file_item table
     */
    protected function createOroAttachmentFileItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_attachment_file_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('file_id', 'integer', ['notnull' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => '0']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['file_id']);
    }

    /**
     * Add oro_attachment_file_item foreign keys.
     */
    protected function addOroAttachmentFileItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_attachment_file_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
