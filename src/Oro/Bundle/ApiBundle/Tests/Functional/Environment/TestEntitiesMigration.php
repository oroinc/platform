<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TestEntitiesMigration implements Migration, ExtendExtensionAwareInterface, ActivityExtensionAwareInterface
{
    /** @var ExtendExtension */
    private $extendExtension;

    /** @var ActivityExtension */
    private $activityExtension;

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
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createTestDepartmentTable($schema);
        $this->createTestPersonTable($schema);
        $this->createTestDefaultAndNullTable($schema);
        $this->createTestWithoutIdGeneratorTable($schema);
        $this->createTestCompositeIdentifierTable($schema);
        $this->createTestCustomIdentifierTables($schema);
        $this->createTestNestedObjectsTable($schema);
        $this->createTestAllDataTypesTable($schema);
        $this->createTestCustomEntityTables($schema);
        $this->createTestEntityTables($schema);
        $this->createTestProductTable($schema);
        $this->createTestOrderTables($schema);
        $this->createTestOverrideClassEntityTables($schema);
    }

    /**
     * Create test_api_department table
     *
     * @param Schema $schema
     */
    private function createTestDepartmentTable(Schema $schema)
    {
        if ($schema->hasTable('test_api_department')) {
            return;
        }

        $table = $schema->createTable('test_api_department');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['business_unit_owner_id']);
        $table->addIndex(['organization_id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Create test_api_person table
     *
     * @param Schema $schema
     */
    private function createTestPersonTable(Schema $schema)
    {
        if ($schema->hasTable('test_api_person')) {
            return;
        }

        $table = $schema->createTable('test_api_person');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('department_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('position', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['department_id']);
        $table->addIndex(['business_unit_owner_id']);
        $table->addIndex(['organization_id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('test_api_department'),
            ['department_id'],
            ['id']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Create test_api_default_and_null table
     *
     * @param Schema $schema
     */
    private function createTestDefaultAndNullTable(Schema $schema)
    {
        if ($schema->hasTable('test_api_default_and_null')) {
            return;
        }

        $table = $schema->createTable('test_api_default_and_null');
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
     * Create test_api_nested_objects table
     *
     * @param Schema $schema
     */
    public function createTestNestedObjectsTable(Schema $schema)
    {
        if ($schema->hasTable('test_api_nested_objects')) {
            return;
        }

        $table = $schema->createTable('test_api_nested_objects');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('related_class', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('related_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['parent_id']);
        $table->addForeignKeyConstraint($table, ['parent_id'], ['id']);

        $tableLinks = $schema->createTable('test_api_nested_objects_links');
        $tableLinks->addColumn('owner_id', 'integer');
        $tableLinks->addColumn('link_id', 'integer');
        $tableLinks->setPrimaryKey(['owner_id', 'link_id']);
        $tableLinks->addIndex(['owner_id']);
        $tableLinks->addIndex(['link_id']);
        $tableLinks->addForeignKeyConstraint($table, ['owner_id'], ['id']);
        $tableLinks->addForeignKeyConstraint($schema->getTable('test_api_custom_id'), ['link_id'], ['id']);
    }

    /**
     * Create test_api_without_id_generator table
     *
     * @param Schema $schema
     */
    private function createTestWithoutIdGeneratorTable(Schema $schema)
    {
        if ($schema->hasTable('test_api_without_id_generator')) {
            return;
        }

        $table = $schema->createTable('test_api_without_id_generator');
        $table->addColumn('id', 'string', ['notnull' => true, 'length' => 50]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_api_composite_id table
     *
     * @param Schema $schema
     */
    private function createTestCompositeIdentifierTable(Schema $schema)
    {
        if ($schema->hasTable('test_api_composite_id')) {
            return;
        }

        $table = $schema->createTable('test_api_composite_id');
        $table->addColumn('key1', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('key2', 'integer', ['notnull' => true]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('parent_key1', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('parent_key2', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['key1', 'key2']);
        $table->addIndex(['parent_key1', 'parent_key2']);
        $table->addForeignKeyConstraint($table, ['parent_key1', 'parent_key2'], ['key1', 'key2']);

        $tableChildren = $schema->createTable('test_api_composite_id_children');
        $tableChildren->addColumn('parent_key1', 'string', ['length' => 255]);
        $tableChildren->addColumn('parent_key2', 'integer');
        $tableChildren->addColumn('child_key1', 'string', ['length' => 255]);
        $tableChildren->addColumn('child_key2', 'integer');
        $tableChildren->setPrimaryKey(['parent_key1', 'parent_key2', 'child_key1', 'child_key2']);
        $tableChildren->addIndex(['parent_key1', 'parent_key2']);
        $tableChildren->addIndex(['child_key1', 'child_key2']);
        $tableChildren->addForeignKeyConstraint($table, ['parent_key1', 'parent_key2'], ['key1', 'key2']);
        $tableChildren->addForeignKeyConstraint($table, ['child_key1', 'child_key2'], ['key1', 'key2']);
    }

    /**
     * Create test_api_custom_id and test_api_custom_composite_id tables
     *
     * @param Schema $schema
     */
    private function createTestCustomIdentifierTables(Schema $schema)
    {
        if ($schema->hasTable('test_api_custom_id')
            || $schema->hasTable('test_api_custom_composite_id')
        ) {
            return;
        }

        $table1 = $schema->createTable('test_api_custom_id');
        $table1->addColumn('id', 'integer', ['autoincrement' => true]);
        $table1->addColumn('key', 'string', ['notnull' => true, 'length' => 255]);
        $table1->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table1->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table1->setPrimaryKey(['id']);
        $table1->addIndex(['parent_id']);
        $table1->addForeignKeyConstraint($table1, ['parent_id'], ['id']);

        $table2 = $schema->createTable('test_api_custom_composite_id');
        $table2->addColumn('id', 'integer', ['autoincrement' => true]);
        $table2->addColumn('key1', 'string', ['notnull' => true, 'length' => 255]);
        $table2->addColumn('key2', 'integer', ['notnull' => true]);
        $table2->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table2->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table2->setPrimaryKey(['id']);
        $table2->addIndex(['parent_id']);
        $table2->addForeignKeyConstraint($table2, ['parent_id'], ['id']);

        $table1Children = $schema->createTable('test_api_custom_id_children');
        $table1Children->addColumn('parent_id', 'integer');
        $table1Children->addColumn('child_id', 'integer');
        $table1Children->setPrimaryKey(['parent_id', 'child_id']);
        $table1Children->addIndex(['parent_id']);
        $table1Children->addIndex(['child_id']);
        $table1Children->addForeignKeyConstraint($table1, ['parent_id'], ['id']);
        $table1Children->addForeignKeyConstraint($table1, ['child_id'], ['id']);

        $table2Children = $schema->createTable('test_api_custom_composite_id_c');
        $table2Children->addColumn('parent_id', 'integer');
        $table2Children->addColumn('child_id', 'integer');
        $table2Children->setPrimaryKey(['parent_id', 'child_id']);
        $table2Children->addIndex(['parent_id']);
        $table2Children->addIndex(['child_id']);
        $table2Children->addForeignKeyConstraint($table2, ['parent_id'], ['id']);
        $table2Children->addForeignKeyConstraint($table2, ['child_id'], ['id']);
    }

    /**
     * Create test_api_composite_id table
     *
     * @param Schema $schema
     */
    private function createTestAllDataTypesTable(Schema $schema)
    {
        if ($schema->hasTable('test_api_all_data_types')) {
            return;
        }

        $table = $schema->createTable('test_api_all_data_types');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field_string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('field_text', 'text', ['notnull' => false]);
        $table->addColumn('field_int', 'integer', ['notnull' => false]);
        $table->addColumn('field_smallint', 'smallint', ['notnull' => false]);
        $table->addColumn('field_bigint', 'bigint', ['notnull' => false]);
        $table->addColumn('field_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('field_decimal', 'decimal', ['notnull' => false, 'precision' => 10, 'scale' => 6]);
        $table->addColumn('field_float', 'float', ['notnull' => false]);
        $table->addColumn('field_array', 'array', ['notnull' => false]);
        $table->addColumn('field_simple_array', 'simple_array', ['notnull' => false]);
        $table->addColumn('field_json_array', 'json_array', ['notnull' => false]);
        $table->addColumn('field_datetime', 'datetime', ['notnull' => false]);
        $table->addColumn('field_date', 'date', ['notnull' => false]);
        $table->addColumn('field_time', 'time', ['notnull' => false]);
        $table->addColumn('field_guid', 'guid', ['notnull' => false]);
        $table->addColumn('field_percent', 'percent', ['notnull' => false]);
        $table->addColumn('field_money', 'money', ['notnull' => false]);
        $table->addColumn('field_duration', 'duration', ['notnull' => false]);
        $table->addColumn('field_money_value', 'money_value', ['notnull' => false]);
        $table->addColumn('field_currency', 'currency', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create custom entity tables
     *
     * @param Schema $schema
     */
    private function createTestCustomEntityTables(Schema $schema)
    {
        if ($schema->hasTable('oro_ext_testapie1') || $schema->hasTable('oro_ext_testapie2')) {
            return;
        }

        $customOwner = ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]];
        $withoutDefault = ['extend' => ['without_default' => true]];

        $t1 = $this->extendExtension->createCustomEntityTable($schema, 'TestApiE1');
        $t1->addColumn('name', 'string', ['length' => 255, OroOptions::KEY => $customOwner]);
        $this->addEnumField($schema, $t1, 'enumField', 'api_enum1');
        $this->addEnumField($schema, $t1, 'multiEnumField', 'api_enum2', true);
        $t2 = $this->extendExtension->createCustomEntityTable($schema, 'TestApiE2');
        $t2->addColumn('name', 'string', ['length' => 255, OroOptions::KEY => $customOwner]);

        // unidirectional many-to-one
        $this->addManyToOneRelation($schema, $t1, 'uniM2O', $t2);
        // bidirectional many-to-one
        $this->addManyToOneRelation($schema, $t1, 'biM2O', $t2);
        $this->addManyToOneInverseRelation($schema, $t1, 'biM2O', $t2, 'biM2OOwners');

        // unidirectional many-to-many
        $this->addManyToManyRelation($schema, $t1, 'uniM2M', $t2);
        // unidirectional many-to-many without default
        $this->addManyToManyRelation($schema, $t1, 'uniM2MnD', $t2, $withoutDefault);
        // bidirectional many-to-many
        $this->addManyToManyRelation($schema, $t1, 'biM2M', $t2);
        $this->addManyToManyInverseRelation($schema, $t1, 'biM2M', $t2, 'biM2MOwners');
        // bidirectional many-to-many without default
        $this->addManyToManyRelation($schema, $t1, 'biM2MnD', $t2, $withoutDefault);
        $this->addManyToManyInverseRelation($schema, $t1, 'biM2MnD', $t2, 'biM2MnDOwners');

        // unidirectional one-to-many
        $this->addOneToManyRelation($schema, $t1, 'uniO2M', $t2);
        // unidirectional one-to-many without default
        $this->addOneToManyRelation($schema, $t1, 'uniO2MnD', $t2, $withoutDefault);
        // bidirectional one-to-many
        $this->addOneToManyRelation($schema, $t1, 'biO2M', $t2);
        $this->addOneToManyInverseRelation($schema, $t1, 'biO2M', $t2, 'biO2MOwner');
        // bidirectional one-to-many without default
        $this->addOneToManyRelation($schema, $t1, 'biO2MnD', $t2, $withoutDefault);
        $this->addOneToManyInverseRelation($schema, $t1, 'biO2MnD', $t2, 'biO2MnDOwner');
    }

    /**
     * @param Schema $s
     * @param Table  $t
     * @param string $name
     * @param string $code
     * @param bool   $isMultiple
     */
    private function addEnumField($s, $t, $name, $code, $isMultiple = false)
    {
        $this->extendExtension->addEnumField(
            $s,
            $t,
            $name,
            $code,
            $isMultiple,
            false,
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    /**
     * @param Schema $s
     * @param Table  $t
     * @param string $name
     * @param Table  $tt
     * @param array  $options
     */
    private function addManyToOneRelation($s, $t, $name, $tt, array $options = [])
    {
        $options = \array_merge_recursive(
            [
                'extend' => [
                    'owner'        => ExtendScope::OWNER_CUSTOM,
                    'target_field' => 'id'
                ]
            ],
            $options
        );
        $this->extendExtension->addManyToOneRelation($s, $t, $name, $tt, 'name', $options);
    }

    /**
     * @param Schema $s
     * @param Table  $t
     * @param string $name
     * @param Table  $tt
     * @param string $targetName
     */
    private function addManyToOneInverseRelation($s, $t, $name, $tt, $targetName)
    {
        $this->extendExtension->addManyToOneInverseRelation(
            $s,
            $t,
            $name,
            $tt,
            $targetName,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    /**
     * @param Schema $s
     * @param Table  $t
     * @param string $name
     * @param Table  $tt
     * @param array  $options
     */
    private function addManyToManyRelation($s, $t, $name, $tt, array $options = [])
    {
        $options = \array_merge_recursive(
            [
                'extend' => [
                    'owner'           => ExtendScope::OWNER_CUSTOM,
                    'target_title'    => ['id'],
                    'target_detailed' => ['id'],
                    'target_grid'     => ['id']
                ]
            ],
            $options
        );
        $this->extendExtension->addManyToManyRelation($s, $t, $name, $tt, ['name'], ['name'], ['name'], $options);
    }

    /**
     * @param Schema $s
     * @param Table  $t
     * @param string $name
     * @param Table  $tt
     * @param string $targetName
     */
    private function addManyToManyInverseRelation($s, $t, $name, $tt, $targetName)
    {
        $this->extendExtension->addManyToManyInverseRelation(
            $s,
            $t,
            $name,
            $tt,
            $targetName,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    /**
     * @param Schema $s
     * @param Table  $t
     * @param string $name
     * @param Table  $tt
     * @param array  $options
     */
    private function addOneToManyRelation($s, $t, $name, $tt, array $options = [])
    {
        $options = \array_merge_recursive(
            [
                'extend' => [
                    'owner'           => ExtendScope::OWNER_CUSTOM,
                    'target_title'    => ['id'],
                    'target_detailed' => ['id'],
                    'target_grid'     => ['id']
                ]
            ],
            $options
        );
        $this->extendExtension->addOneToManyRelation($s, $t, $name, $tt, ['name'], ['name'], ['name'], $options);
    }

    /**
     * @param Schema $s
     * @param Table  $t
     * @param string $name
     * @param Table  $tt
     * @param string $targetName
     */
    private function addOneToManyInverseRelation($s, $t, $name, $tt, $targetName)
    {
        $this->extendExtension->addOneToManyInverseRelation(
            $s,
            $t,
            $name,
            $tt,
            $targetName,
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    /**
     * Create the following tables:
     * * test_api_owner
     * * test_api_target
     * * test_api_activity
     *
     * @param Schema $schema
     */
    private function createTestEntityTables(Schema $schema)
    {
        if ($schema->hasTable('test_api_owner')
            || $schema->hasTable('test_api_target')
            || $schema->hasTable('test_api_activity')
        ) {
            return;
        }

        $ownerTable = $schema->createTable('test_api_owner');
        $ownerTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $ownerTable->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $ownerTable->addColumn('target_id', 'integer', ['notnull' => false]);
        $ownerTable->addColumn(
            'extend_description',
            'text',
            [
                'oro_options' => [
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
                ]
            ]
        );
        $ownerTable->setPrimaryKey(['id']);
        $ownerTable->addIndex(['target_id']);

        $targetTable = $schema->createTable('test_api_target');
        $targetTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $targetTable->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $targetTable->setPrimaryKey(['id']);
        $targetTable->addIndex(['name'], 'test_api_t_name_idx');

        $ownerTable->addForeignKeyConstraint(
            $targetTable,
            ['target_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $targetsRelTable = $schema->createTable('test_api_rel_targets');
        $targetsRelTable->addColumn('owner_id', 'integer');
        $targetsRelTable->addColumn('target_id', 'integer');
        $targetsRelTable->setPrimaryKey(['owner_id', 'target_id']);
        $targetsRelTable->addIndex(['owner_id']);
        $targetsRelTable->addIndex(['target_id']);
        $targetsRelTable->addForeignKeyConstraint($ownerTable, ['owner_id'], ['id']);
        $targetsRelTable->addForeignKeyConstraint($targetTable, ['target_id'], ['id']);

        $activityTable = $schema->createTable('test_api_activity');
        $activityTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $activityTable->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $activityTable->setPrimaryKey(['id']);
        $this->activityExtension->addActivityAssociation(
            $schema,
            $activityTable->getName(),
            $ownerTable->getName(),
            true
        );
        $this->activityExtension->addActivityAssociation(
            $schema,
            $activityTable->getName(),
            $targetTable->getName(),
            true
        );
    }

    /**
     * Create test_api_product table
     *
     * @param Schema $schema
     */
    private function createTestProductTable(Schema $schema)
    {
        if ($schema->hasTable('test_api_product')) {
            return;
        }

        $table = $schema->createTable('test_api_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_api_order and test_api_order_line_item tables
     *
     * @param Schema $schema
     */
    protected function createTestOrderTables(Schema $schema)
    {
        if ($schema->hasTable('test_api_order') || $schema->hasTable('test_api_order_line_item')) {
            return;
        }

        $orderTable = $schema->createTable('test_api_order');
        $orderTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $orderTable->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $orderTable->setPrimaryKey(['id']);

        $orderLineItemTable = $schema->createTable('test_api_order_line_item');
        $orderLineItemTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $orderLineItemTable->addColumn('product_id', 'integer', ['notnull' => false]);
        $orderLineItemTable->addColumn('order_id', 'integer', ['notnull' => false]);
        $orderLineItemTable->addColumn('quantity', 'float', ['notnull' => false]);
        $orderLineItemTable->setPrimaryKey(['id']);
        $orderLineItemTable->addIndex(['product_id']);
        $orderLineItemTable->addIndex(['order_id']);
        $orderLineItemTable->addForeignKeyConstraint(
            $schema->getTable('test_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $orderLineItemTable->addForeignKeyConstraint(
            $orderTable,
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Create the following tables:
     * * test_api_override_owner
     * * test_api_override_target
     * * test_api_override_a_target
     * * test_api_override_activity
     *
     * @param Schema $schema
     */
    private function createTestOverrideClassEntityTables(Schema $schema)
    {
        if ($schema->hasTable('test_api_override_owner')
            || $schema->hasTable('test_api_override_target')
            || $schema->hasTable('test_api_override_a_target')
            || $schema->hasTable('test_api_override_activity')
        ) {
            return;
        }

        $ownerTable = $schema->createTable('test_api_override_owner');
        $ownerTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $ownerTable->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $ownerTable->addColumn('target_id', 'integer', ['notnull' => false]);
        $ownerTable->addColumn('another_target_id', 'integer', ['notnull' => false]);
        $ownerTable->setPrimaryKey(['id']);
        $ownerTable->addIndex(['target_id']);
        $ownerTable->addIndex(['another_target_id']);

        $targetTable = $schema->createTable('test_api_override_target');
        $targetTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $targetTable->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $targetTable->setPrimaryKey(['id']);
        $targetTable->addIndex(['name'], 'test_api_override_t_name_idx');

        $anotherTargetTable = $schema->createTable('test_api_override_a_target');
        $anotherTargetTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $anotherTargetTable->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $anotherTargetTable->setPrimaryKey(['id']);
        $anotherTargetTable->addIndex(['name'], 'test_api_override_a_t_name_idx');

        $ownerTable->addForeignKeyConstraint(
            $targetTable,
            ['target_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $targetsRelTable = $schema->createTable('test_api_override_rel_targets');
        $targetsRelTable->addColumn('owner_id', 'integer');
        $targetsRelTable->addColumn('target_id', 'integer');
        $targetsRelTable->setPrimaryKey(['owner_id', 'target_id']);
        $targetsRelTable->addIndex(['owner_id']);
        $targetsRelTable->addIndex(['target_id']);
        $targetsRelTable->addForeignKeyConstraint($ownerTable, ['owner_id'], ['id']);
        $targetsRelTable->addForeignKeyConstraint($targetTable, ['target_id'], ['id']);

        $ownerTable->addForeignKeyConstraint(
            $anotherTargetTable,
            ['another_target_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $anotherTargetsRelTable = $schema->createTable('test_api_override_a_rel_ts');
        $anotherTargetsRelTable->addColumn('owner_id', 'integer');
        $anotherTargetsRelTable->addColumn('target_id', 'integer');
        $anotherTargetsRelTable->setPrimaryKey(['owner_id', 'target_id']);
        $anotherTargetsRelTable->addIndex(['owner_id']);
        $anotherTargetsRelTable->addIndex(['target_id']);
        $anotherTargetsRelTable->addForeignKeyConstraint($ownerTable, ['owner_id'], ['id']);
        $anotherTargetsRelTable->addForeignKeyConstraint($anotherTargetTable, ['target_id'], ['id']);

        $activityTable = $schema->createTable('test_api_override_activity');
        $activityTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $activityTable->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $activityTable->setPrimaryKey(['id']);
        $this->activityExtension->addActivityAssociation(
            $schema,
            $activityTable->getName(),
            $ownerTable->getName(),
            true
        );
        $this->activityExtension->addActivityAssociation(
            $schema,
            $activityTable->getName(),
            $targetTable->getName(),
            true
        );
        $this->activityExtension->addActivityAssociation(
            $schema,
            $activityTable->getName(),
            $anotherTargetTable->getName(),
            true
        );
    }
}
