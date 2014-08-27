<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_2;

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
        self::addOrganizationDashboardTable($schema);
        self::createOroDashboardActiveTable($schema);
        self::addOroDashboardActiveForeignKeys($schema);

        //Copy data from oro_dashboard_active into oro_dashboard_active_copy, rename and drop it
        $queries->addPostQuery(
            "INSERT INTO oro_dashboard_active_copy (user_id, dashboard_id)
             SELECT user_id, dashboard_id
             FROM oro_dashboard_active;

             DROP TABLE oro_dashboard_active;
             ALTER TABLE oro_dashboard_active_copy RENAME TO oro_dashboard_active;"
        );
    }

    /**
     * Adds organization_id into oro_dashboard
     *
     * @param Schema $schema
     */
    public static function addOrganizationDashboardTable(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_DF2802EF32C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_dashboard_active table
     *
     * @param Schema $schema
     */
    protected function createOroDashboardActiveTable(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard_active');
        if ($table->hasIndex('IDX_858BA17EB9D04D2B')) {
            $table->removeForeignKey('FK_858BA17EB9D04D2B');
            $table->dropIndex('IDX_858BA17EB9D04D2B');
        }
        $table = $schema->createTable('oro_dashboard_active_copy');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('dashboard_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_858BA17EA76ED395', []);
        $table->addIndex(['dashboard_id'], 'IDX_858BA17EB9D04D2B', []);
        $table->addIndex(['organization_id'], 'IDX_858BA17E32C8A3DE', []);
    }

    /**
     * Add oro_dashboard_active foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDashboardActiveForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard_active_copy');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
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
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

}
