<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDataGridBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_3';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroGridViewTable($schema);
        $this->createOroGridViewUserTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroGridViewTable(Schema $schema)
    {
        $table = $schema->createTable('oro_grid_view');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('filtersData', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('sortersData', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('columnsData', 'array', ['comment' => '(DC2Type:array)', 'notnull' => false]);
        $table->addColumn('gridName', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'IDX_5B73FBCB9EB185F9', []);
        $table->addIndex(['organization_id'], 'IDX_5B73FBCB32C8A3DE', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroGridViewUserTable(Schema $schema)
    {
        $table = $schema->createTable('oro_grid_view_user');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('grid_view_id', 'integer', ['notnull' => false]);
        $table->addColumn('alias', 'string', ['length' => 255]);
        $table->addColumn('grid_name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_USER_ID_GRID', []);
        $table->addIndex(['grid_view_id'], 'IDX_GRID_VIEW_GRID', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_grid_view'),
            ['grid_view_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
