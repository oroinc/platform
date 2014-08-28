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
        self::dropOroDashboardActiveTableIndexes($schema);
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
     * Drop oro_dashboard_active table indexes
     *
     * @param Schema $schema
     */
    protected function dropOroDashboardActiveTableIndexes(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard_active');
        if ($table->hasIndex('IDX_858BA17EB9D04D2B')) {
            $table->removeForeignKey('FK_858BA17EB9D04D2B');
            $table->dropIndex('IDX_858BA17EB9D04D2B');
        }
    }
}
