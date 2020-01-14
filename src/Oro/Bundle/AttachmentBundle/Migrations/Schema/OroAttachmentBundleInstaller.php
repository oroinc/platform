<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_0\OroAttachmentBundle;
use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_1\OroAttachmentBundle as OroAttachmentBundle1;
use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_2\OroAttachmentBundle as OroAttachmentOrganization;
use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_4\AddOriginalFilenameIndex;
use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_7\AddFileUuidColumn;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAttachmentBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_8';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroAttachmentBundle::createFileTable($schema);
        $this->addParentEntityClassEntityIdColumns($schema);
        AddFileUuidColumn::addUuidColumn($schema);
        OroAttachmentBundle1::createAttachmentTable($schema);
        OroAttachmentOrganization::addOrganizationFields($schema);
        AddOriginalFilenameIndex::addOriginalFilenameIndex($schema);

        /** Tables generation **/
        $this->createOroAttachmentFileItemTable($schema);

        /** Foreign keys generation **/
        $this->addOroAttachmentFileItemForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addParentEntityClassEntityIdColumns(Schema $schema): void
    {
        $table = $schema->getTable('oro_attachment_file');
        $table->addColumn('parent_entity_class', 'string', ['notnull' => false, 'length' => 512]);
        $table->addColumn('parent_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_entity_field_name', 'string', ['notnull' => false, 'length' => 50]);
    }

    /**
     * Create oro_attachment_file_item table
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
