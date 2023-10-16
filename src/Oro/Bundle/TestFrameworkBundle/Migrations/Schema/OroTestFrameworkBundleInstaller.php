<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareTrait;

/**
 * IMPORTANT!!!
 * Please, do not create new migrations in `Migrations/Schema` folder!
 * Add new schema migrations to this installer instead.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroTestFrameworkBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    ScopeExtensionAwareInterface,
    SerializedFieldsExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    use ScopeExtensionAwareTrait;
    use AttachmentExtensionAwareTrait;

    public const ENUM_FIELD_NAME = 'testEnumField';
    public const ENUM_FIELD_CODE = 'test_enum_code';
    public const MULTIENUM_FIELD_NAME = 'testMultienumField';
    public const MULTIENUM_FIELD_CODE = 'test_multienum_code';

    /** @var ActivityExtension */
    protected $activityExtension;

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var SerializedFieldsExtension */
    private $serializedFieldsExtension;

    /**
     * {@inheritdoc}
     */
    public function setSerializedFieldsExtension(SerializedFieldsExtension $serializedFieldsExtension)
    {
        $this->serializedFieldsExtension = $serializedFieldsExtension;
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
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
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
        $this->createTestCustomEntityTablesWithCascadeOption($schema);
        $this->createTestDepartmentTable($schema);
        $this->createTestEmployeeTable($schema);
        $this->createTestProductTable($schema);
        $this->createTestProductTypeTable($schema);
        $this->createTestUserOwnershipTable($schema);
        $this->createTestExtendedEntityTable($schema);

        /** Entity extensions generation */
        $this->extendScopeForTestActivity($schema);

        /** Foreign keys generation **/
        $this->addTestSearchItemForeignKeys($schema);
        $this->addTestSearchItemValueForeignKeys($schema);
        $this->addTestActivityForeignKeys($schema);
        $this->addTestEmployeeForeignKeys($schema);
        $this->addTestProductForeignKeys($schema);
        $this->addTestUserOwnershipForeignKeys($schema);

        $this->activityExtension->addActivityAssociation($schema, 'test_activity', 'test_activity_target', true);

        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            'test_activity_target',
            true
        );

        // add activity association if calendar package is installed
        if ($schema->hasTable('oro_calendar_event')) {
            $this->activityExtension->addActivityAssociation(
                $schema,
                'oro_calendar_event',
                'test_activity_target',
                true
            );
        }

        $this->addAttributeFamilyRelationForTestActivityTarget($schema);

        $this->createOroTestFrameworkTestEntityFieldsTable($schema);
        $this->createOroTestFrameworkManyToManyRelationToTestEntityFieldsTable($schema);
        $this->addOroTestFrameworkTestEntityFieldsForeignKeys($schema);
        $this->addOroTestFrameworkManyToManyRelationToTestEntityFieldsForeignKeys($schema);
        $this->addOroTestFrameworkTestEntityExtendFields($schema);
    }

    /**
     * Create test_activity_target table
     */
    protected function createTestActivityTargetTable(Schema $schema)
    {
        $table = $schema->createTable('test_activity_target');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_workflow_aware_entity table
     */
    protected function createTestWorkflowAwareEntityTable(Schema $schema)
    {
        $table = $schema->createTable('test_workflow_aware_entity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_department table
     */
    protected function createTestDepartmentTable(Schema $schema)
    {
        $table = $schema->createTable('test_department');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_employee table
     */
    protected function createTestEmployeeTable(Schema $schema)
    {
        $table = $schema->createTable('test_employee');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('department_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('position', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['department_id'], 'IDX_A305D658AE80F5DF', []);
    }

    /**
     * Create test_product table
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
     */
    protected function createTestSearchItem2Table(Schema $schema)
    {
        $table = $schema->createTable('test_search_item2');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create test_search_item_value table
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
     * Create custom entity tables
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function createTestCustomEntityTables(Schema $schema)
    {
        $extendFields = [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'target_title' => ['id'],
            'target_detailed' => ['id'],
            'target_grid' => ['id']
        ];

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
            ['extend' => $extendFields]
        );
        // bidirectional many-to-one
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table1,
            'biM2OTarget',
            $table2,
            'name',
            ['extend' => $extendFields]
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
            ['extend' => $extendFields]
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
            ['extend' => array_merge($extendFields, ['without_default' => true])]
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
            ['extend' => $extendFields]
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
            ['extend' => array_merge($extendFields, ['without_default' => true])]
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
            ['extend' => $extendFields]
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
            ['extend' => array_merge($extendFields, ['without_default' => true])]
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
            ['extend' => array_merge($extendFields, ['without_default' => true])]
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
        // enum field
        $this->extendExtension->addEnumField(
            $schema,
            $table1,
            self::ENUM_FIELD_NAME,
            self::ENUM_FIELD_CODE,
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'entity' => ['label' => 'extend.entity.test.test_enum_field'],
                'attribute' => ['is_attribute' => true, 'searchable' => true, 'filterable' => true],
                'importexport' => ['excluded' => true]
            ]
        );
        // multi-enum field
        $this->extendExtension->addEnumField(
            $schema,
            $table1,
            self::MULTIENUM_FIELD_NAME,
            self::MULTIENUM_FIELD_CODE,
            true,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'entity' => ['label' => 'extend.entity.test.test_multienum_field'],
                'attribute' => ['is_attribute' => true, 'searchable' => true, 'filterable' => true],
                'importexport' => ['excluded' => true]
            ]
        );
    }

    /**
     * Create custom entity tables that have associations with "cascade"=['all'] option
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function createTestCustomEntityTablesWithCascadeOption(Schema $schema)
    {
        $extendFields = [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'target_title' => ['id'],
            'target_detailed' => ['id'],
            'target_grid' => ['id']
        ];

        $table1 = $this->extendExtension->createCustomEntityTable($schema, 'TestEntity3');
        $table1->addColumn(
            'name',
            'string',
            [
                'length'        => 255,
                OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
            ]
        );
        $table2 = $this->extendExtension->createCustomEntityTable($schema, 'TestEntity4');
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
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
        );
        // bidirectional many-to-one
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table1,
            'biM2OTarget',
            $table2,
            'name',
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
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
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'cascade' => ['all']]]
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
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
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
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
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
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'cascade' => ['all']]]
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
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
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
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'cascade' => ['all']]]
        );
        $this->extendExtension->addOneToManyInverseRelation(
            $schema,
            $table1,
            'biO2MTargets',
            $table2,
            'biO2MOwner',
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'cascade' => ['all']]]
        );
    }

    /**
     * Create test_user_ownership table
     */
    protected function createTestUserOwnershipTable(Schema $schema)
    {
        $table = $schema->createTable('test_user_ownership');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_673C997D32C8A3DE', []);
        $table->addIndex(['owner_id'], 'IDX_673C997D7E3C61F9', []);
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
     * Add test_employee foreign keys.
     */
    protected function addTestEmployeeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_employee');
        $table->addForeignKeyConstraint(
            $schema->getTable('test_department'),
            ['department_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add test_product foreign keys.
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

    /**
     * Add test_user_ownership foreign keys.
     */
    protected function addTestUserOwnershipForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('test_user_ownership');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    public function addAttributeFamilyRelationForTestActivityTarget(Schema $schema)
    {
        $table = $schema->getTable('test_activity_target');

        $table->addColumn('attribute_family_id', 'integer', ['notnull' => false]);
        $table->addIndex(['attribute_family_id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attribute_family'),
            ['attribute_family_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'RESTRICT']
        );
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    protected function createTestExtendedEntityTable(Schema $schema)
    {
        $extendFields = [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'target_title' => ['id'],
            'target_detailed' => ['id'],
            'target_grid' => ['id']
        ];

        $table = $schema->createTable('test_extended_entity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('regular_field', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);

        $table->addColumn(
            'name',
            'string',
            [
                'length' => 255,
                OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
            ]
        );

        // enum field
        $this->extendExtension->addEnumField(
            $schema,
            $table,
            'testExtendedEntityEnumAttribute',
            'test_extended_entity_enum_attribute',
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'entity' => ['label' => 'extend.entity.test_extended_entity_enum_attribute.label'],
                'attribute' => ['is_attribute' => true, 'searchable' => true, 'filterable' => true],
                'importexport' => ['excluded' => true]
            ]
        );

        $customEntityTable = $this->extendExtension->createCustomEntityTable($schema, 'TestEntity5');
        $customEntityTable->addColumn(
            'name',
            'string',
            [
                'length' => 255,
                OroOptions::KEY => ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
            ]
        );

        // unidirectional many-to-one
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $customEntityTable,
            'uniM2OTarget',
            $table,
            'name',
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
        );
        // bidirectional many-to-one
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $customEntityTable,
            'biM2OTarget',
            $table,
            'name',
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
        );
        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $customEntityTable,
            'biM2OTarget',
            $table,
            'biM2OOwners',
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'cascade' => ['all']]]
        );

        // unidirectional many-to-many
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $customEntityTable,
            'uniM2MTargets',
            $table,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
        );
        // bidirectional many-to-many
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $customEntityTable,
            'biM2MTargets',
            $table,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
        );
        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            $customEntityTable,
            'biM2MTargets',
            $table,
            'biM2MOwners',
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'cascade' => ['all']]]
        );

        // unidirectional one-to-many
        $this->extendExtension->addOneToManyRelation(
            $schema,
            $customEntityTable,
            'uniO2MTargets',
            $table,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => array_merge($extendFields, ['cascade' => ['all']])]
        );
        // bidirectional one-to-many
        $this->extendExtension->addOneToManyRelation(
            $schema,
            $customEntityTable,
            'biO2MTargets',
            $table,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'cascade' => ['all']]]
        );
        $this->extendExtension->addOneToManyInverseRelation(
            $schema,
            $customEntityTable,
            'biO2MTargets',
            $table,
            'biO2MOwner',
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'cascade' => ['all']]]
        );
        $this->serializedFieldsExtension->addSerializedField(
            $table,
            'serialized_attribute',
            'string',
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                ],
                'attribute' => [
                    'is_attribute' => true
                ]
            ]
        );
    }

    private function createOroTestFrameworkTestEntityFieldsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_test_framework_test_entity_fields');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('integer_field', 'integer', ['notnull' => false]);
        $table->addColumn('float_field', 'float', ['notnull' => false]);
        $table->addColumn('decimal_field', 'decimal', ['notnull' => false]);
        $table->addColumn('smallint_field', 'smallint', ['notnull' => false]);
        $table->addColumn('bigint_field', 'bigint', ['notnull' => false]);
        $table->addColumn('text_field', 'text', ['notnull' => false]);
        $table->addColumn('date_field', 'date', ['notnull' => false]);
        $table->addColumn('datetime_field', 'datetime', ['notnull' => false]);
        $table->addColumn('boolean_field', 'boolean', ['notnull' => false]);
        $table->addColumn('html_field', 'text', ['notnull' => false]);
        $table->addColumn('string_field', 'string', ['length' => 10]);
        $table->addColumn('many_to_one_relation_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    private function createOroTestFrameworkManyToManyRelationToTestEntityFieldsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_test_framework_many_to_many_relation_to_test_entity_fields');
        $table->addColumn('test_entity_fields_id', 'integer');
        $table->addColumn('oro_product_id', 'integer');
        $table->setPrimaryKey(['test_entity_fields_id', 'oro_product_id']);
    }

    private function addOroTestFrameworkTestEntityFieldsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_test_framework_test_entity_fields');
        $table->addForeignKeyConstraint(
            $schema->getTable('test_extended_entity'),
            ['many_to_one_relation_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    private function addOroTestFrameworkManyToManyRelationToTestEntityFieldsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_test_framework_many_to_many_relation_to_test_entity_fields');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_framework_test_entity_fields'),
            ['test_entity_fields_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('test_extended_entity'),
            ['oro_product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addOroTestFrameworkTestEntityExtendFields(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_test_framework_test_entity_fields',
            'multienum_field',
            'test_entity_fields_multienum_field',
            true
        );
        $this->extendExtension->addEnumField(
            $schema,
            'oro_test_framework_test_entity_fields',
            'enum_field',
            'test_entity_fields_enum_field'
        );

        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_test_framework_test_entity_fields',
            'image_field'
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            'test_extended_entity', // Owning table name
            'oro_test_framework_test_entity_fields', // Field Name
            'oro_test_framework_test_entity_fields', // Relation table name
            'string_field',
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'nullable' => true, 'on_delete' => 'SET NULL']
            ]
        );
    }
}
