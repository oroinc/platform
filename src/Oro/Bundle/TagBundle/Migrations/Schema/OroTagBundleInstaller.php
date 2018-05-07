<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroTagBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_10';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroTagTaggingTable($schema);
        $this->createOroTagTagTable($schema);

        /** Foreign keys generation **/
        $this->addOroTagTaggingForeignKeys($schema);
        $this->addOroTagTagForeignKeys($schema);

        $this->addTaxonomy($schema);
        $this->addOroTaxonomyForeignKeys($schema);
    }

    /**
     * Create oro_tag_tagging table
     *
     * @param Schema $schema
     */
    protected function createOroTagTaggingTable(Schema $schema)
    {
        $table = $schema->createTable('oro_tag_tagging');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tag_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('entity_name', 'string', ['length' => 100]);
        $table->addColumn('record_id', 'integer', []);
        $table->addIndex(['entity_name', 'record_id'], 'entity_name_idx', []);
        $table->addIndex(['tag_id'], 'idx_50107502bad26311', []);
        $table->addIndex(['user_owner_id'], 'idx_501075029eb185f9', []);
        $table->addUniqueIndex(['tag_id', 'entity_name', 'record_id', 'user_owner_id'], 'tagging_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_tag_tag table
     *
     * @param Schema $schema
     */
    protected function createOroTagTagTable(Schema $schema)
    {
        $table = $schema->createTable('oro_tag_tag');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 50]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['name', 'organization_id'], 'name_organization_idx', []);
        $table->addIndex(['organization_id'], 'idx_caf0db5732c8a3de', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'idx_caf0db579eb185f9', []);
    }

    /**
     * Add oro_tag_tagging foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTagTaggingForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroTagTagForeignKeys(Schema $schema)
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
    }

    /**
     * @param Schema $schema
     */
    protected function addTaxonomy(Schema $schema)
    {
        /** Generate table oro_tag_tag **/
        $table = $schema->createTable('oro_tag_taxonomy');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 50]);
        $table->addColumn('background_color', 'string', ['length' => 7, 'notnull' => false]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name', 'organization_id'], 'tag_taxonomy_name_organization_idx');
        $table->addIndex(['user_owner_id'], 'tag_taxonomy_user_owner_idx', []);
        /** End of generate table oro_tag_tag **/

        $tagTable = $schema->getTable('oro_tag_tag');
        $tagTable->addColumn('taxonomy_id', 'integer', ['notnull' => false]);

        $tagTable->addForeignKeyConstraint(
            $table,
            ['taxonomy_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_tag_taxonomy foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTaxonomyForeignKeys(Schema $schema)
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
