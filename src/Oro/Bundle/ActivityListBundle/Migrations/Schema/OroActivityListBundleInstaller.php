<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Creates all tables and indexes for the ActivityList bundle.
 */
class OroActivityListBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroActivityListTable($schema);
        $this->createOroActivityOwnerTable($schema);

        /** Foreign keys generation **/
        $this->addOroActivityListForeignKeys($schema);
        $this->addOroActivityOwnerForeignKeys($schema);
    }

    /**
     * Create oro_activity_list table
     *
     * @param Schema $schema
     */
    protected function createOroActivityListTable(Schema $schema)
    {
        $table = $schema->createTable('oro_activity_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_editor_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('verb', 'string', ['length' => 32]);
        $table->addColumn('subject', 'string', ['length' => 255]);
        $table->addColumn('related_activity_class', 'string', ['length' => 255]);
        $table->addColumn('related_activity_id', 'integer', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_B1F9F0132C8A3DE', []);
        $table->addIndex(['updated_at'], 'oro_activity_list_updated_idx', []);
        $table->addIndex(['user_owner_id'], 'IDX_B1F9F019EB185F9', []);
        $table->addIndex(['user_editor_id'], 'IDX_B1F9F01697521A8', []);
        $table->addIndex(['related_activity_class'], 'al_related_activity_class');
        $table->addIndex(['related_activity_id'], 'al_related_activity_id');
    }

    /**
     * Create oro_activity_owner table
     *
     * @param Schema $schema
     */
    protected function createOroActivityOwnerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_activity_owner');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('activity_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['activity_id', 'user_id'], 'UNQ_activity_owner');
        $table->addIndex(['activity_id']);
        $table->addIndex(['organization_id']);
        $table->addIndex(['user_id'], 'idx_oro_activity_owner_user_id', []);
    }

    /**
     * Add oro_activity_list foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroActivityListForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_activity_list');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_editor_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_activity_owner foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroActivityOwnerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_activity_owner');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_activity_list'),
            ['activity_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
