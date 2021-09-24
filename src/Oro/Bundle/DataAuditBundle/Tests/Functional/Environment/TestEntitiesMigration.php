<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TestEntitiesMigration implements Migration, ExtendExtensionAwareInterface
{
    /**
     * @var ExtendExtension
     */
    private $extendExtension;

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
        if ($schema->hasTable('oro_test_dataaudit_owner')) {
            return;
        }

        $this->createTestDataAuditOwnerTable($schema);
        $this->createTestDataAuditChildTable($schema);
        $this->createTestDataAuditRelations($schema);
        $this->createTestDataAuditManyToManyTable($schema);
        $this->createTestDataAuditManyToManyUnidirectionalTable($schema);

        $this->addTestDataAuditOwnerForeignKeys($schema);
        $this->addTestDataAuditChildForeignKeys($schema);
        $this->addTestDataAuditManyToManyForeignKeys($schema);
        $this->addTestDataAuditManyToManyUnidirectionalForeignKeys($schema);
    }

    /**
     * Create oro_test_dataaudit_owner table
     */
    protected function createTestDataAuditOwnerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_test_dataaudit_owner');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('not_auditable_property', 'text', ['notnull' => false]);
        $table->addColumn('child_id', 'integer', ['notnull' => false]);
        $table->addColumn('array_property', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('binary_property', 'binary', ['notnull' => false, 'comment' => '(DC2Type:binary)']);
        $table->addColumn('bigint_property', 'bigint', ['notnull' => false, 'comment' => '(DC2Type:bigint)']);
        $table->addColumn('blob_property', 'blob', ['notnull' => false, 'comment' => '(DC2Type:blob)']);
        $table->addColumn('boolean_property', 'boolean', ['notnull' => false, 'comment' => '(DC2Type:boolean)']);
        $table->addColumn(
            'config_object_property',
            'config_object',
            ['notnull' => false, 'comment' => '(DC2Type:config_object)']
        );
        $table->addColumn(
            'crypted_string_property',
            'crypted_string',
            ['notnull' => false, 'comment' => '(DC2Type:crypted_string)']
        );
        $table->addColumn('currency_property', 'currency', ['notnull' => false, 'comment' => '(DC2Type:currency)']);
        $table->addColumn('date_property', 'date', ['notnull' => false, 'comment' => '(DC2Type:date)']);
        $table->addColumn('date_time_property', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn(
            'date_time_tz_property',
            'datetimetz',
            ['notnull' => false, 'comment' => '(DC2Type:datetimetz)']
        );
        $table->addColumn(
            'decimal_property',
            'decimal',
            ['notnull' => false, 'comment' => '(DC2Type:decimal)', 'precision' => 19, 'scale' => 4]
        );
        $table->addColumn('duration_property', 'duration', ['notnull' => false, 'comment' => '(DC2Type:duration)']);
        $table->addColumn('float_property', 'float', ['notnull' => false, 'comment' => '(DC2Type:float)']);
        $table->addColumn('guid_property', 'guid', ['notnull' => false, 'comment' => '(DC2Type:guid)']);
        $table->addColumn('integer_property', 'integer', ['notnull' => false, 'comment' => '(DC2Type:integer)']);
        $table->addColumn(
            'json_array_property',
            'json_array',
            ['notnull' => false, 'comment' => '(DC2Type:json_array)']
        );
        $table->addColumn('money_property', 'money', ['notnull' => false, 'comment' => '(DC2Type:money)']);
        $table->addColumn(
            'money_value_property',
            'money_value',
            ['notnull' => false, 'comment' => '(DC2Type:money_value)']
        );
        $table->addColumn('object_property', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('child_unidirectional_id', 'integer', ['notnull' => false]);
        $table->addColumn('child_cascade_id', 'integer', ['notnull' => false]);
        $table->addColumn('child_orphan_id', 'integer', ['notnull' => false]);
        $table->addColumn('percent_property', 'percent', ['notnull' => false, 'comment' => '(DC2Type:percent)']);
        $table->addColumn(
            'simple_array_property',
            'simple_array',
            ['notnull' => false, 'comment' => '(DC2Type:simple_array)']
        );
        $table->addColumn('smallint_property', 'smallint', ['notnull' => false, 'comment' => '(DC2Type:smallint)']);
        $table->addColumn('string_property', 'text', ['notnull' => false, 'comment' => '(DC2Type:text)']);
        $table->addColumn('text_property', 'text', ['notnull' => false, 'comment' => '(DC2Type:text)']);
        $table->addColumn('time_property', 'time', ['notnull' => false, 'comment' => '(DC2Type:time)']);
        $table->addColumn(
            'date_immutable_property',
            'date_immutable',
            ['notnull' => false, 'comment' => '(DC2Type:date_immutable)']
        );
        $table->addColumn(
            'dateinterval_property',
            'dateinterval',
            ['notnull' => false, 'comment' => '(DC2Type:dateinterval)']
        );
        $table->addColumn(
            'datetime_immutable_property',
            'datetime_immutable',
            ['notnull' => false, 'comment' => '(DC2Type:datetime_immutable)']
        );
        $table->addColumn(
            'datetimetz_immutable_property',
            'datetimetz_immutable',
            ['notnull' => false, 'comment' => '(DC2Type:datetimetz_immutable)']
        );
        $table->addColumn('json_property', 'json', ['notnull' => false, 'comment' => '(DC2Type:json)']);
        $table->addColumn(
            'time_immutable_property',
            'time_immutable',
            ['notnull' => false, 'comment' => '(DC2Type:time_immutable)']
        );
        $table->addUniqueIndex(['child_id']);
        $table->addUniqueIndex(['child_cascade_id']);
        $table->addUniqueIndex(['child_orphan_id']);
        $table->addUniqueIndex(['child_unidirectional_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_test_dataaudit_child table
     */
    protected function createTestDataAuditChildTable(Schema $schema)
    {
        $table = $schema->createTable('oro_test_dataaudit_child');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('string_property', 'text', ['notnull' => false]);
        $table->addColumn('owner_one_to_many_id', 'integer', ['notnull' => false]);
        $table->addColumn('not_auditable_property', 'text', ['notnull' => false]);
        $table->addIndex(['owner_one_to_many_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function createTestDataAuditRelations(Schema $schema)
    {
        $this->extendExtension->addEnumField(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'enumProperty',
            'audit_enum',
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'dataaudit' => ['auditable' => true],
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_DEFAULT,
            ]
        );

        $this->extendExtension->addEnumField(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'multiEnumProperty',
            'audit_muenum',
            true,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'dataaudit' => ['auditable' => true],
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_DEFAULT,
            ]
        );

        $extendFields = [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'target_title' => ['id'],
            'target_detailed' => ['id'],
            'target_grid' => ['id'],
        ];

        // unidirectional many-to-one
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'uniM2O',
            $schema->getTable('oro_test_dataaudit_child'),
            'string_property',
            ['extend' => $extendFields, 'dataaudit' => ['auditable' => true]]
        );
        // bidirectional many-to-one
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'biM2O',
            $schema->getTable('oro_test_dataaudit_child'),
            'string_property',
            ['extend' => $extendFields, 'dataaudit' => ['auditable' => true]]
        );
        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'biM2O',
            $schema->getTable('oro_test_dataaudit_child'),
            'biM2OOwners',
            ['string_property'],
            ['string_property'],
            ['string_property'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM], 'dataaudit' => ['auditable' => true]]
        );

        // unidirectional many-to-many
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'uniM2M',
            $schema->getTable('oro_test_dataaudit_child'),
            ['string_property'],
            ['string_property'],
            ['string_property'],
            ['extend' => $extendFields, 'dataaudit' => ['auditable' => true]]
        );
        // bidirectional many-to-many
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'biM2M',
            $schema->getTable('oro_test_dataaudit_child'),
            ['string_property'],
            ['string_property'],
            ['string_property'],
            ['extend' => $extendFields, 'dataaudit' => ['auditable' => true]]
        );
        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'biM2M',
            $schema->getTable('oro_test_dataaudit_child'),
            'biM2MOwners',
            ['string_property'],
            ['string_property'],
            ['string_property'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM], 'dataaudit' => ['auditable' => true]]
        );

        // unidirectional one-to-many
        $this->extendExtension->addOneToManyRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'uniO2M',
            $schema->getTable('oro_test_dataaudit_child'),
            ['string_property'],
            ['string_property'],
            ['string_property'],
            ['extend' => $extendFields, 'dataaudit' => ['auditable' => true]]
        );
        // bidirectional one-to-many
        $this->extendExtension->addOneToManyRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'biO2M',
            $schema->getTable('oro_test_dataaudit_child'),
            ['string_property'],
            ['string_property'],
            ['string_property'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM], 'dataaudit' => ['auditable' => true]]
        );
        $this->extendExtension->addOneToManyInverseRelation(
            $schema,
            $schema->getTable('oro_test_dataaudit_owner'),
            'biO2M',
            $schema->getTable('oro_test_dataaudit_child'),
            'biO2MOwner',
            'string_property',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM], 'dataaudit' => ['auditable' => true]]
        );
    }

    /**
     * Create oro_test_dataaudit_many2many table
     */
    protected function createTestDataAuditManyToManyTable(Schema $schema)
    {
        $table = $schema->createTable('oro_test_dataaudit_many2many');
        $table->addColumn('child_id', 'integer', ['notnull' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['owner_id', 'child_id']);
        $table->addIndex(['owner_id']);
        $table->addUniqueIndex(['child_id']);
    }

    /**
     * Create oro_test_dataaudit_many2many_u table
     */
    protected function createTestDataAuditManyToManyUnidirectionalTable(Schema $schema)
    {
        $table = $schema->createTable('oro_test_dataaudit_many2many_u');
        $table->addColumn('child_id', 'integer', ['notnull' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['owner_id', 'child_id']);
        $table->addIndex(['owner_id']);
        $table->addUniqueIndex(['child_id']);
    }

    /**
     * Add oro_test_dataaudit_owner foreign keys.
     */
    protected function addTestDataAuditOwnerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_test_dataaudit_owner');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_unidirectional_id'],
            ['id']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_cascade_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_orphan_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_test_dataaudit_child foreign keys.
     */
    protected function addTestDataAuditChildForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_test_dataaudit_child');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_one_to_many_id'],
            ['id']
        );
    }

    /**
     * Add oro_test_dataaudit_many2many foreign keys.
     */
    protected function addTestDataAuditManyToManyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_test_dataaudit_many2many');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_id'],
            ['id']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id'],
            ['unique' => true]
        );
    }

    /**
     * Add oro_test_dataaudit_many2many_u foreign keys.
     */
    protected function addTestDataAuditManyToManyUnidirectionalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_test_dataaudit_many2many_u');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_owner'),
            ['owner_id'],
            ['id']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_test_dataaudit_child'),
            ['child_id'],
            ['id'],
            ['unique' => true]
        );
    }
}
