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
        self::addOrganization($schema);
        self::addPKActiveDashboards($schema, $queries);
    }

    /**
     * Adds organization_id into oro_dashboard
     *
     * @param Schema $schema
     */
    public static function addOrganization(Schema $schema)
    {
/*        $table = $schema->getTable('oro_dashboard');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_DF2802EF32C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );*/

        $table = $schema->getTable('oro_dashboard_active');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_858BA17E32C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Removes old pk by user_id, added id auto_increment into oro_dashboard_active
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     */
    public static function addPKActiveDashboards(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_dashboard_active');

        if ($table->hasForeignKey('FK_858BA17EA76ED395')) {
            $table->removeForeignKey('FK_858BA17EA76ED395');
        }
        $table->dropPrimaryKey();

        $queries->addPostQuery('ALTER TABLE oro_dashboard_active ADD id INT AUTO_INCREMENT primary key NOT NULL;');
        $table->addUniqueIndex(['user_id', 'organization_id'], 'uq_user_org');
    }
}
