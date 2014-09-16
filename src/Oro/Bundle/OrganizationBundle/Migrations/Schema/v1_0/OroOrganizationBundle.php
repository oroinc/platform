<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrganizationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroOrganizationTable($schema);
        self::oroBusinessUnitTable($schema);

        self::oroBusinessUnitForeignKeys($schema);
    }

    /**
     * Generate table oro_organization
     *
     * @param Schema $schema
     */
    public static function oroOrganizationTable(Schema $schema)
    {
        /** Generate table oro_organization **/
        $table = $schema->createTable('oro_organization');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_organization **/
    }

    /**
     * Generate table oro_business_unit
     *
     * @param Schema $schema
     */
    public static function oroBusinessUnitTable(Schema $schema)
    {
        /** Generate table oro_business_unit **/
        $table = $schema->createTable('oro_business_unit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', []);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('website', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('fax', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_C033B2D532C8A3DE', []);
        $table->addIndex(['business_unit_owner_id'], 'IDX_C033B2D559294170', []);
        /** End of generate table oro_business_unit **/
    }

    /**
     * Generate foreign keys for table oro_business_unit
     *
     * @param Schema $schema
     */
    public static function oroBusinessUnitForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_business_unit **/
        $table = $schema->getTable('oro_business_unit');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_business_unit **/
    }
}
