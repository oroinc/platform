<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroActivityListBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroActivityListTable($schema);

        /** Foreign keys generation **/
        $this->addOroActivityListForeignKeys($schema);
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
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_B1F9F0132C8A3DE', []);
        $table->addIndex(['updated_at'], 'oro_activity_list_updated_idx', []);
        $table->addIndex(['user_owner_id'], 'IDX_B1F9F019EB185F9', []);
        $table->addIndex(['user_editor_id'], 'IDX_B1F9F01697521A8', []);
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
}
