<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroTestFrameworkBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    ScopeExtensionAwareInterface
{
    use ScopeExtensionAwareTrait;

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
    public function getMigrationVersion()
    {
        return 'v1_4';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createTestActivityTargetTable($schema);
        $this->createTestWorkflowAwareEntityTable($schema);
        $this->createTestSearchItemTable($schema);
        $this->createTestSearchItem2Table($schema);
        $this->createTestSearchItemValueTable($schema);
        $this->createTestSearchProductTable($schema);
        $this->createTestActivityTable($schema);
        $this->createTestCustomEntityTables($schema);
        $this->createTestDefaultAndNullTable($schema);
        $this->createTestDepartmentTable($schema);
        $this->createTestPersonTable($schema);
        $this->createTestProductTable($schema);
        $this->createTestProductTypeTable($schema);
        $this->createTestAuditDataTables($schema);

        /** Entity extensions generation */
        $this->extendScopeForTestActivity($schema);

        /** Foreign keys generation **/
        $this->addTestSearchItemForeignKeys($schema);
        $this->addTestSearchItemValueForeignKeys($schema);
        $this->addTestActivityForeignKeys($schema);
        $this->addTestPersonForeignKeys($schema);
        $this->addTestProductForeignKeys($schema);

        $this->activityExtension->addActivityAssociation($schema, 'test_activity', 'test_activity_target', true);

        // add activity association if calendar package is installed
        if ($schema->hasTable('oro_calendar_event')) {
            $this->activityExtension->addActivityAssociation(
                $schema,
                'oro_calendar_event',
                'test_activity_target',
                true
            );
        }
    }

    /**
     * Create test_activity_target table
     *
     * @param Schema $schema
     */
    protected function createTestActivityTargetTable(Schema $schema)
    {
        $table = $schema->createTable('test_activity_target');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_workflow_aware_entity table
     *
     * @param Schema $schema
     */
    protected function createTestWorkflowAwareEntityTable(Schema $schema)
    {
        $table = $schema->createTable('test_workflow_aware_entity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_default_and_null table
     *
     * @param Schema $schema
     */
    protected function createTestDefaultAndNullTable(Schema $schema)
    {
        $table = $schema->createTable('test_default_and_null');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('with_default_value_string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('without_default_value_string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('with_default_value_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('without_default_value_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('with_default_value_integer', 'integer', ['notnull' => false]);
        $table->addColumn('without_default_value_integer', 'integer', ['notnull' => false]);
        $table->addColumn('with_df_not_blank', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('with_df_not_null', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('with_not_blank', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('with_not_null', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
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
     * Create test_search_item table
     *
     * @param Schema $schema
     */
    protected function createTestSearchItemTable(Schema $schema)
    {
        $table = $schema->createTable('test_search_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('stringvalue', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('integervalue', 'integer', ['notnull' => false]);
        $table->addColumn('decimalvalue', 'decimal', ['notnull' => false, 'scale' => 2]);
        $table->addColumn('floatvalue', 'float', ['notnull' => false]);
        $table->addColumn('booleanvalue', 'boolean', ['notnull' => false]);
        $table->addColumn('blobvalue', 'blob', ['notnull' => false]);
        $table->addColumn('arrayvalue', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('datetimevalue', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('guidvalue', 'guid', ['notnull' => false]);
        $table->addColumn('objectvalue', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('phone1', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_search_item2 table
     *
     * @param Schema $schema
     */
    protected function createTestSearchItem2Table(Schema $schema)
    {
        $table = $schema->createTable('test_search_item2');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_search_item_value table
     *
     * @param Schema $schema
     */
    protected function createTestSearchItemValueTable(Schema $schema)
    {
        $table = $schema->createTable('test_search_item_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_search_product table
     *
     * @param Schema $schema
     */
    protected function createTestSearchProductTable(Schema $schema)
    {
        $table = $schema->createTable('test_search_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_activity table
     *
     * @param Schema $schema
     */
    protected function createTestActivityTable(Schema $schema)
    {
        $table = $schema->createTable('test_activity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('message', 'text', []);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addIndex(['owner_id'], 'idx_test_activity_owner_id', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createTestAuditDataTables(Schema $schema)
    {
        $ownerTable = $schema->createTable('oro_test_dataaudit_owner');
        $ownerTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $ownerTable->addColumn('string_property', 'text', ['notnull' => false]);
        $ownerTable->addColumn('not_auditable_property', 'text', ['notnull' => false]);
        $ownerTable->addColumn('int_property', 'integer', ['notnull' => false]);
        $ownerTable->addColumn('serialized_property', 'text', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $ownerTable->addColumn('json_property', 'text', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $ownerTable->addColumn('date_property', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $ownerTable->addColumn('child_id', 'integer', ['notnull' => false]);
        $ownerTable->addUniqueIndex(['child_id'], 'UNIQ_B001FBEDD62C21B', []);
        $ownerTable->setPrimaryKey(['id']);

        $childTable = $schema->createTable('oro_test_dataaudit_child');
        $childTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $childTable->addColumn('string_property', 'text', ['notnull' => false]);
        $childTable->addColumn('owner_one_to_many_id', 'integer', ['notnull' => false]);
        $childTable->addColumn('not_auditable_property', 'text', ['notnull' => false]);
        $childTable->addIndex(['owner_one_to_many_id'], 'idx_test_dataaudit_child_owner_one_to_many_id', []);
        $childTable->setPrimaryKey(['id']);
        $childTable->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_one_to_many_id'],
            ['id']
        );

        $ownerTable->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id']
        );

        $childrenTable = $schema->createTable('oro_test_dataaudit_many2many');
        $childrenTable->addColumn('child_id', 'integer', ['notnull' => true]);
        $childrenTable->addColumn('owner_id', 'integer', ['notnull' => true]);
        $childrenTable->setPrimaryKey(['owner_id', 'child_id']);
        $childrenTable->addIndex(['owner_id'], 'FK_B67A508B7E3C61F9');
        $childrenTable->addUniqueIndex(['child_id'], 'UNIQ_B67A508BDD62C21B');
        $childrenTable->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_id'],
            ['id']
        );
        $childrenTable->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id'],
            ['unique' => true]
        );
    }

    /**
     * Create custom entity tables
     *
     * @param Schema $schema
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function createTestCustomEntityTables(Schema $schema)
    {
        $table1 = $this->extendExtension->createCustomEntityTable($schema, 'TestEntity1');
        $table1->addColumn(
            'name',
            'string',
            [
                'length'        => 255,
                OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
            ]
        );
        $table2 = $this->extendExtension->createCustomEntityTable($schema, 'TestEntity2');
        $table2->addColumn(
            'name',
            'string',
            [
                'length'        => 255,
                OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
            ]
        );

        // unidirectional many-to-one
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table1,
            'uniM2OTarget',
            $table2,
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        // bidirectional many-to-one
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table1,
            'biM2OTarget',
            $table2,
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $table1,
            'biM2OTarget',
            $table2,
            'biM2OOwners',
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );

        // unidirectional many-to-many
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $table1,
            'uniM2MTargets',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        // unidirectional many-to-many without default
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $table1,
            'uniM2MNDTargets',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
        // bidirectional many-to-many
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $table1,
            'biM2MTargets',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            $table1,
            'biM2MTargets',
            $table2,
            'biM2MOwners',
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        // bidirectional many-to-many without default
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $table1,
            'biM2MNDTargets',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            $table1,
            'biM2MNDTargets',
            $table2,
            'biM2MNDOwners',
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );

        // unidirectional one-to-many
        $this->extendExtension->addOneToManyRelation(
            $schema,
            $table1,
            'uniO2MTargets',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        // unidirectional one-to-many without default
        $this->extendExtension->addOneToManyRelation(
            $schema,
            $table1,
            'uniO2MNDTargets',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
        // bidirectional one-to-many
        $this->extendExtension->addOneToManyRelation(
            $schema,
            $table1,
            'biO2MTargets',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        $this->extendExtension->addOneToManyInverseRelation(
            $schema,
            $table1,
            'biO2MTargets',
            $table2,
            'biO2MOwner',
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
        // bidirectional one-to-many without default
        $this->extendExtension->addOneToManyRelation(
            $schema,
            $table1,
            'biO2MNDTargets',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]]
        );
        $this->extendExtension->addOneToManyInverseRelation(
            $schema,
            $table1,
            'biO2MNDTargets',
            $table2,
            'biO2MNDOwner',
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
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
     * @param Schema $schema
     */
    private function extendScopeForTestActivity($schema)
    {
        $this->scopeExtension->addScopeAssociation($schema, 'test_activity', 'test_activity', 'id');
    }

    /**
     * Add test_search_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestSearchItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_search_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add test_search_item_value foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestSearchItemValueForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_search_item_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('test_search_item'),
            ['entity_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add test_activity foreign keys.
     *
     * @param Schema $schema
     */
    protected function addTestActivityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_activity');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
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
