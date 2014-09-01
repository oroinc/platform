<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDashboardBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_dashboard_active_copy');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('dashboard_id', 'integer', ['notnull' => false]);
        $table->addIndex(['user_id'], 'IDX_858BA17EA76ED395', []);
        $table->addIndex(['dashboard_id'], 'IDX_858BA17EB9D04D2B', []);
        $table->addIndex(['organization_id'], 'IDX_858BA17E32C8A3DE', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard'),
            ['dashboard_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $queries->addPostQuery(
            "INSERT INTO oro_dashboard_active_copy (user_id, dashboard_id)
             SELECT user_id, dashboard_id
             FROM oro_dashboard_active;

             DROP TABLE oro_dashboard_active;
             ALTER TABLE oro_dashboard_active_copy RENAME TO oro_dashboard_active;"
        );
    }
}
