<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\UpdateOwnershipTypeQuery;

class OroDashboardBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addOrganizationDashboardTable($schema);
        $this->dropOroDashboardActiveTableIndexes($schema);

        //Add organization fields to ownership entity config
        $queries->addQuery(
            new UpdateOwnershipTypeQuery(
                'Oro\Bundle\DashboardBundle\Entity\Dashboard',
                [
                    'organization_field_name' => 'organization',
                    'organization_column_name' => 'organization_id'
                ]
            )
        );
    }

    private function addOrganizationDashboardTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_dashboard');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_DF2802EF32C8A3DE');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function dropOroDashboardActiveTableIndexes(Schema $schema): void
    {
        $table = $schema->getTable('oro_dashboard_active');
        if ($table->hasIndex('IDX_858BA17EB9D04D2B')) {
            $table->removeForeignKey('FK_858BA17EB9D04D2B');
            $table->dropIndex('IDX_858BA17EB9D04D2B');
        }
    }
}
