<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ReportBundle\Migrations\Schema\v1_0\OroReportBundle as OroReportSchemaMigration1_0;
use Oro\Bundle\SecurityBundle\Migrations\Schema\UpdateOwnershipTypeQuery;

class OroReportBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOrganization($schema);

        //Add organization fields to ownership entity config
        $queries->addQuery(
            new UpdateOwnershipTypeQuery(
                'Oro\Bundle\ReportBundle\Entity\Report',
                [
                    'organization_field_name' => 'organization',
                    'organization_column_name' => 'organization_id'
                ]
            )
        );
    }

    /**
     * Adds organization_id field
     *
     * @param Schema $schema
     */
    public static function addOrganization(Schema $schema)
    {
        $table = $schema->getTable('oro_report');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_B48821B632C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
