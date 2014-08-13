<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroOrganizationBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroBusinessUnitTable($schema);
        $this->createOroOrganizationTable($schema);

        /** Foreign keys generation **/
        $this->addOroBusinessUnitForeignKeys($schema);
    }

    /**
     * Create oro_business_unit table
     *
     * @param Schema $schema
     */
    protected function createOroBusinessUnitTable(Schema $schema)
    {
        $table = $schema->createTable('oro_business_unit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('website', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('fax', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['business_unit_owner_id'], 'idx_c033b2d559294170', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_c033b2d532c8a3de', []);
    }

    /**
     * Create oro_organization table
     *
     * @param Schema $schema
     */
    protected function createOroOrganizationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_organization');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('enabled', 'boolean', ['default' => '1']);
        $table->addUniqueIndex(['name'], 'uniq_bb42b65d5e237e06');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_business_unit foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroBusinessUnitForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_business_unit');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
