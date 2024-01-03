<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAttachmentBundleInstaller implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_11';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroAttachmentTable($schema);
        $this->createOroAttachmentFileItemTable($schema);
        $this->createOroAttachmentFileTable($schema);

        /** Foreign keys generation **/
        $this->addOroAttachmentForeignKeys($schema);
        $this->addOroAttachmentFileItemForeignKeys($schema);
    }

    /**
     * Create oro_attachment table
     */
    private function createOroAttachmentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_attachment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('file_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_attachment_file_item table
     */
    private function createOroAttachmentFileItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_attachment_file_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('file_id', 'integer', ['notnull' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => '0']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['file_id']);
    }

    /**
     * Create oro_attachment_file table
     */
    private function createOroAttachmentFileTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_attachment_file');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('uuid', 'guid', ['notnull' => false]);
        $table->addColumn('file_size', 'integer', ['notnull' => false]);
        $table->addColumn('filename', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('original_filename', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('extension', 'string', ['notnull' => false, 'length' => 10]);
        $table->addColumn('mime_type', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('parent_entity_class', 'string', ['notnull' => false, 'length' => 512]);
        $table->addColumn('parent_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_entity_field_name', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('external_url', 'string', ['length' => 1024, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['original_filename'], 'att_file_orig_filename_idx');
        $table->addIndex(['uuid'], 'att_file_uuid_idx');
    }

    /**
     * Add oro_attachment foreign keys.
     */
    private function addOroAttachmentForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_attachment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_attachment_file_item foreign keys.
     */
    private function addOroAttachmentFileItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_attachment_file_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
