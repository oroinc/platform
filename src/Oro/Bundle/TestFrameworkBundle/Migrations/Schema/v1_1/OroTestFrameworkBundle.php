<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestFrameworkBundle implements Migration, ActivityExtensionAwareInterface, ExtendExtensionAwareInterface
{
    /** @var ActivityExtension */
    protected $activityExtension;

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createTestDepartmentTable($schema);
        $this->createTestPersonTable($schema);
        $this->createTestProductTable($schema);
        $this->createTestProductTypeTable($schema);

        $this->addTestPersonForeignKeys($schema);
        $this->addTestProductForeignKeys($schema);

        $this->activityExtension->addActivityAssociation($schema, 'oro_calendar_event', 'test_activity_target', true);
    }

    /**
     * Create test_department table
     *
     * @param Schema $schema
     */
    protected function createTestDepartmentTable(Schema $schema)
    {
        $table = $schema->createTable('test_department');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_person table
     *
     * @param Schema $schema
     */
    protected function createTestPersonTable(Schema $schema)
    {
        $table = $schema->createTable('test_person');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('department_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('position', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['department_id'], 'IDX_A305D658AE80F5DF', []);
    }

    /**
     * Create test_product table
     *
     * @param Schema $schema
     */
    protected function createTestProductTable(Schema $schema)
    {
        $table = $schema->createTable('test_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_type', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_type'], 'IDX_F0BD0651367588', []);
    }

    /**
     * Create test_product_type table
     *
     * @param Schema $schema
     */
    protected function createTestProductTypeTable(Schema $schema)
    {
        $table = $schema->createTable('test_product_type');
        $table->addColumn('name', 'string', ['length' => 50]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['name']);
    }

    /**
     * Add test_person foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestPersonForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_person');
        $table->addForeignKeyConstraint(
            $schema->getTable('test_department'),
            ['department_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add test_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('test_product_type'),
            ['product_type'],
            ['name'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
