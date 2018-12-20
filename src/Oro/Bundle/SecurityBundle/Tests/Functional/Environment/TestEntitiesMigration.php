<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TestEntitiesMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createTestSecurityDepartmentTable($schema);
        $this->createTestSecurityPersonTable($schema);
        $this->createTestSecurityOrderTable($schema);
        $this->createTestSecurityCompanyTable($schema);
        $this->createTestSecurityProductTable($schema);
        $this->createTestSecurityOrderProductTable($schema);
    }

    /**
     * Create test_security_department table
     *
     * @param Schema $schema
     */
    protected function createTestSecurityDepartmentTable(Schema $schema)
    {
        if ($schema->hasTable('test_security_department')) {
            return;
        }

        $table = $schema->createTable('test_security_department');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'idx_67aad89b32c8a3de', []);
        $table->addIndex(['business_unit_owner_id'], 'idx_67aad89b59294170', []);
        $table->setPrimaryKey(['id']);
        $this->addTestSecurityDepartmentForeignKeys($schema);
    }

    /**
     * Create test_security_person table
     *
     * @param Schema $schema
     */
    protected function createTestSecurityPersonTable(Schema $schema)
    {
        if ($schema->hasTable('test_security_person')) {
            return;
        }

        $table = $schema->createTable('test_security_person');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('department_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addIndex(['user_id'], 'idx_7cfd1726a76ed395', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_7cfd172632c8a3de', []);
        $table->addIndex(['department_id'], 'idx_7cfd1726ae80f5df', []);
        $this->addTestSecurityPersonForeignKeys($schema);
    }

    /**
     * Create test_security_order table
     *
     * @param Schema $schema
     */
    protected function createTestSecurityOrderTable(Schema $schema)
    {
        if ($schema->hasTable('test_security_order')) {
            return;
        }

        $table = $schema->createTable('test_security_order');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addIndex(['user_id'], 'idx_66d9883fa76ed395', []);
        $table->setPrimaryKey(['id']);
        $this->addTestSecurityOrderForeignKeys($schema);
    }

    /**
     * Create test_security_company table
     *
     * @param Schema $schema
     */
    protected function createTestSecurityCompanyTable(Schema $schema)
    {
        if ($schema->hasTable('test_security_company')) {
            return;
        }

        $table = $schema->createTable('test_security_company');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_249c797d32c8a3de', []);
        $this->addTestSecurityCompanyForeignKeys($schema);
    }

    /**
     * Create test_security_product table
     *
     * @param Schema $schema
     */
    protected function createTestSecurityProductTable(Schema $schema)
    {
        if ($schema->hasTable('test_security_product')) {
            return;
        }

        $table = $schema->createTable('test_security_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addIndex(['organization_id'], 'idx_b869749f32c8a3de', []);
        $table->setPrimaryKey(['id']);
        $this->addTestSecurityProductForeignKeys($schema);
    }

    /**
     * Create test_security_order_product table
     *
     * @param Schema $schema
     */
    protected function createTestSecurityOrderProductTable(Schema $schema)
    {
        if ($schema->hasTable('test_security_order_product')) {
            return;
        }

        $table = $schema->createTable('test_security_order_product');
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('business_unit_id', 'integer', []);
        $table->addIndex(['business_unit_id'], 'idx_6355cf9ea58ecb40', []);
        $table->addIndex(['user_id'], 'idx_6355cf9ea76ed395', []);
        $table->setPrimaryKey(['user_id', 'business_unit_id']);
        $this->addTestSecurityOrderProductForeignKeys($schema);
    }

    /**
     * Add test_security_department foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestSecurityDepartmentForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_security_department');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add test_security_person foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestSecurityPersonForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_security_person');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('test_security_department'),
            ['department_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add test_security_order foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestSecurityOrderForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_security_order');
        $table->addForeignKeyConstraint(
            $schema->getTable('test_security_person'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add test_security_company foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestSecurityCompanyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_security_company');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add test_security_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestSecurityProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_security_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add test_security_order_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestSecurityOrderProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_security_order_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('test_security_product'),
            ['business_unit_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('test_security_order'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
