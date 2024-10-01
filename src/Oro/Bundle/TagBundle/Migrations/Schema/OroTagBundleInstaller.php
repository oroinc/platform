<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTagBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_10';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroTagTaggingTable($schema);
        $this->createOroTagTagTable($schema);
        $this->createOroTaxonomyTable($schema);

        /** Foreign keys generation **/
        $this->addOroTagTaggingForeignKeys($schema);
        $this->addOroTagTagForeignKeys($schema);
        $this->addOroTaxonomyForeignKeys($schema);
    }

    /**
     * Create oro_tag_tagging table
     */
    private function createOroTagTaggingTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_tag_tagging');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tag_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('entity_name', 'string', ['length' => 100]);
        $table->addColumn('record_id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_name', 'record_id'], 'entity_name_idx');
        $table->addIndex(['tag_id'], 'idx_50107502bad26311');
        $table->addIndex(['user_owner_id'], 'idx_501075029eb185f9');
        $table->addUniqueIndex(['tag_id', 'entity_name', 'record_id', 'user_owner_id'], 'tagging_idx');
    }

    /**
     * Create oro_tag_tag table
     */
    private function createOroTagTagTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_tag_tag');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 50]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('taxonomy_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name', 'organization_id'], 'name_organization_idx');
        $table->addIndex(['organization_id'], 'idx_caf0db5732c8a3de');
        $table->addIndex(['user_owner_id'], 'idx_caf0db579eb185f9');
    }

    /**
     * Create oro_tag_taxonomy table
     */
    private function createOroTaxonomyTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_tag_taxonomy');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 50]);
        $table->addColumn('background_color', 'string', ['length' => 7, 'notnull' => false]);
        $table->addColumn('created', 'datetime');
        $table->addColumn('updated', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name', 'organization_id'], 'tag_taxonomy_name_organization_idx');
        $table->addIndex(['user_owner_id'], 'tag_taxonomy_user_owner_idx');
    }

    /**
     * Add oro_tag_tagging foreign keys.
     */
    private function addOroTagTaggingForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_tag_tagging');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tag_tag'),
            ['tag_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_tag_tag foreign keys.
     */
    private function addOroTagTagForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_tag_tag');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tag_taxonomy'),
            ['taxonomy_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_tag_taxonomy foreign keys.
     */
    private function addOroTaxonomyForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_tag_taxonomy');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
