<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\ConfigurationHandlerMock;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ExtendExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityMetadataHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityMetadataHelper;

    /** @var ExtendOptionsManager */
    private $extendOptionsManager;

    /** @var ExtendOptionsParser */
    private $extendOptionsParser;

    protected function setUp(): void
    {
        $this->entityMetadataHelper = $this->createMock(EntityMetadataHelper::class);
        $this->entityMetadataHelper->expects($this->any())
            ->method('getEntityClassesByTableName')
            ->willReturnMap([
                ['table1', ['Acme\AcmeBundle\Entity\Entity1']],
                ['table2', ['Acme\AcmeBundle\Entity\Entity2']],
                ['oro_enum_test_enum', [ExtendHelper::ENTITY_NAMESPACE . 'EV_Test_Enum']],
            ]);
        $this->entityMetadataHelper->expects($this->any())
            ->method('getFieldNameByColumnName')
            ->willReturnArgument(1);

        $this->extendOptionsManager = new ExtendOptionsManager(ConfigurationHandlerMock::getInstance());

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturn(true);

        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany']);

        $this->extendOptionsParser = new ExtendOptionsParser(
            $this->entityMetadataHelper,
            new FieldTypeHelper($entityExtendConfigurationProvider),
            $configManager
        );
    }

    private function getExtendSchema(): ExtendSchema
    {
        return new ExtendSchema($this->extendOptionsManager, new ExtendDbIdentifierNameGenerator());
    }

    private function getExtendExtension(array $config = []): ExtendExtension
    {
        $result = new ExtendExtension(
            $this->extendOptionsManager,
            $this->entityMetadataHelper,
            new PropertyConfigBag($config)
        );
        $result->setNameGenerator(new ExtendDbIdentifierNameGenerator());

        return $result;
    }

    public function testCreateCustomEntityTableWithInvalidEntityName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid entity name. Class: Extend\Entity\Acme\AcmeBundle\Entity\Entity1.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Acme\AcmeBundle\Entity\Entity1'
        );
    }

    public function testCreateCustomEntityTableWithFullClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid entity name. Class: Extend\Entity\Extend\Entity\Entity1.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            ExtendHelper::ENTITY_NAMESPACE . 'Entity1'
        );
    }

    public function testCreateCustomEntityTableWithNameStartsWithDigit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid entity name. Class: Extend\Entity\1Entity.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            '1Entity'
        );
    }

    public function testCreateCustomEntityTableWithNameStartsWithUnderscore()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid entity name. Class: Extend\Entity\_Entity.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            '_Entity'
        );
    }

    public function testCreateCustomEntityTableWithInvalidChars()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid entity name. Class: Extend\Entity\Entity#1.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Entity#1'
        );
    }

    public function testCreateCustomEntityTableWithTooLongName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity name length must be less or equal 55 characters.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'E1234567891234567890123E1234567891234567890123456789012E'
        );
    }

    public function testCreateCustomEntityTableWithInvalidOwner()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "extend.owner" option for a custom entity must be "Custom".');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Entity1',
            [
                'extend' => ['owner' => ExtendScope::OWNER_SYSTEM],
            ]
        );
    }

    public function testCreateCustomEntityTableWithInvalidIsExtend()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "extend.is_extend" option for a custom entity must be TRUE.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $extension->createCustomEntityTable(
            $schema,
            'Entity1',
            [
                'extend' => ['is_extend' => false],
            ]
        );
    }

    public function testCreateCustomEntityTable()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $this->entityMetadataHelper->expects($this->exactly(3))
            ->method('registerEntityClass')
            ->withConsecutive(
                [
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX . 'entity_1',
                    ExtendHelper::ENTITY_NAMESPACE . 'Entity_1'
                ],
                [
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX . 'entity2',
                    ExtendHelper::ENTITY_NAMESPACE . 'Entity2'
                ],
                [
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX . 'entity3',
                    ExtendHelper::ENTITY_NAMESPACE . 'Entity3'
                ]
            );

        $extension->createCustomEntityTable(
            $schema,
            'Entity_1'
        );
        $extension->createCustomEntityTable(
            $schema,
            'Entity2',
            [
                'entity' => ['icon' => 'icon2'],
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
            ]
        );
        $extension->createCustomEntityTable(
            $schema,
            'Entity3',
            [
                'extend' => ['is_extend' => true],
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                sprintf(
                    'CREATE TABLE %sentity_1 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX
                ),
                sprintf(
                    'CREATE TABLE %sentity2 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX
                ),
                sprintf(
                    'CREATE TABLE %sentity3 (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))',
                    ExtendDbIdentifierNameGenerator::CUSTOM_TABLE_PREFIX
                ),
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                ExtendHelper::ENTITY_NAMESPACE . 'Entity_1' => [
                    'configs' => [
                        'extend' => [
                            'owner' => ExtendScope::OWNER_CUSTOM,
                            'is_extend' => true
                        ]
                    ],
                ],
                ExtendHelper::ENTITY_NAMESPACE . 'Entity2' => [
                    'configs' => [
                        'extend' => [
                            'owner' => ExtendScope::OWNER_CUSTOM,
                            'is_extend' => true
                        ],
                        'entity' => ['icon' => 'icon2'],
                    ],
                ],
                ExtendHelper::ENTITY_NAMESPACE . 'Entity3' => [
                    'configs' => [
                        'extend' => [
                            'owner' => ExtendScope::OWNER_CUSTOM,
                            'is_extend' => true
                        ]
                    ],
                ],
            ]
        );
    }

    /**
     * @dataProvider createEnumWithIdentityDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEnum(array $identityFields = [], array $expected = [])
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $expectedTableName = ExtendDbIdentifierNameGenerator::ENUM_TABLE_PREFIX . 'test_status';
        $expectedClassName = ExtendHelper::ENTITY_NAMESPACE . 'EV_Test_Status';

        $this->entityMetadataHelper->expects($this->once())
            ->method('registerEntityClass')
            ->with($expectedTableName, $expectedClassName);

        $this->entityMetadataHelper->expects($this->exactly(4))
            ->method('isEntityClassContainsColumn')
            ->willReturnMap([
                [$expectedClassName, 'id', true],
                [$expectedClassName, 'name', true],
                [$expectedClassName, 'priority', true],
                [$expectedClassName, 'is_default', true],
            ]);

        $extension->createEnum($schema, 'test_status', false, false, false, [], $identityFields);

        $this->assertSchemaSql(
            $schema,
            [
                sprintf(
                    'CREATE TABLE %s (id VARCHAR(32) NOT NULL,'
                    . ' name VARCHAR(255) NOT NULL, priority INT NOT NULL, is_default TINYINT(1) NOT NULL,'
                    . ' PRIMARY KEY(id))',
                    $expectedTableName
                ),
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                $expectedClassName => [
                    'configs' => [
                        'entity' => [
                            'label' => 'oro.entityextend.enums.test_status.entity_label',
                            'plural_label' => 'oro.entityextend.enums.test_status.entity_plural_label',
                            'description' => 'oro.entityextend.enums.test_status.entity_description',
                        ],
                        'extend' => [
                            'owner' => ExtendScope::OWNER_SYSTEM,
                            'is_extend' => true,
                            'table' => 'oro_enum_test_status',
                            'inherit' => ExtendHelper::BASE_ENUM_VALUE_CLASS
                        ],
                        'enum' => [
                            'code' => 'test_status',
                            'public' => false,
                            'multiple' => false
                        ],
                    ],
                    'mode' => ConfigModel::MODE_HIDDEN,
                    'fields' => [
                        'id' => [
                            'configs' => [
                                'entity' => [
                                    'label' => 'oro.entityextend.enumvalue.id.label',
                                    'description' => 'oro.entityextend.enumvalue.id.description',
                                ],
                                'importexport' => ['identity' => $expected['id']],
                                'extend' => ['length' => 32, 'nullable' => false],
                            ],
                            'type' => 'string'
                        ],
                        'name' => [
                            'configs' => [
                                'entity' => [
                                    'label' => 'oro.entityextend.enumvalue.name.label',
                                    'description' => 'oro.entityextend.enumvalue.name.description',
                                ],
                                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                                'importexport' => ['identity' => $expected['name']],
                                'extend' => ['length' => 255],
                            ],
                            'type' => 'string'
                        ],
                        'priority' => [
                            'configs' => [
                                'entity' => [
                                    'label' => 'oro.entityextend.enumvalue.priority.label',
                                    'description' => 'oro.entityextend.enumvalue.priority.description',
                                ],
                                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE]
                            ],
                            'type' => 'integer',
                        ],
                        'default' => [
                            'configs' => [
                                'entity' => [
                                    'label' => 'oro.entityextend.enumvalue.default.label',
                                    'description' => 'oro.entityextend.enumvalue.default.description',
                                ],
                                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE]
                            ],
                            'type' => 'boolean',
                        ],
                    ]
                ],
            ]
        );
    }

    public function createEnumWithIdentityDataProvider(): array
    {
        return [
            '`id` is identity field' => [
                'identityFields' => ['id'],
                'expected' => ['id' => true, 'name' => false]
            ],
            '`name` is identity field' => [
                'identityFields' => ['name'],
                'expected' => ['id' => false, 'name' => true]
            ],
            '`id` and `name` are identity field' => [
                'identityFields' => ['id', 'name'],
                'expected' => ['id' => true, 'name' => true]
            ],
        ];
    }

    /**
     * @dataProvider createEnumWithInvalidIdentityFieldDataProvider
     */
    public function testCreateEnumWithInvalidIdentityField(
        string $exception,
        string $exceptionMessage,
        array $identityFields = []
    ): void {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        $extension->createEnum($schema, 'test_status', false, false, false, [], $identityFields);
    }

    public function createEnumWithInvalidIdentityFieldDataProvider(): array
    {
        return [
            'with not allowed identify fields' => [
                'exception' => \InvalidArgumentException::class,
                'exceptionMessage' =>
                    'The identification fields can only be: id, name. '.
                    'Current invalid fields: priority, is_default.',
                'identityFields' => ['id', 'name', 'priority', 'is_default']
            ],
            'with empty identify fields' => [
                'exception' => \InvalidArgumentException::class,
                'exceptionMessage' => 'At least one identify field is required',
                'identityFields' => []
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateImmutableEnum()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $expectedTableName = ExtendDbIdentifierNameGenerator::ENUM_TABLE_PREFIX . 'test_status';
        $expectedClassName = ExtendHelper::ENTITY_NAMESPACE . 'EV_Test_Status';

        $this->entityMetadataHelper->expects($this->once())
            ->method('registerEntityClass')
            ->with($expectedTableName, $expectedClassName);

        $this->entityMetadataHelper->expects($this->exactly(4))
            ->method('isEntityClassContainsColumn')
            ->willReturnMap([
                [$expectedClassName, 'id', true],
                [$expectedClassName, 'name', true],
                [$expectedClassName, 'priority', true],
                [$expectedClassName, 'is_default', true],
            ]);

        $extension->createEnum(
            $schema,
            'test_status',
            true,
            true,
            true,
            [
                'test_scope' => [
                    'test_attr' => 'test'
                ]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                sprintf(
                    'CREATE TABLE %s (id VARCHAR(32) NOT NULL,'
                    . ' name VARCHAR(255) NOT NULL, priority INT NOT NULL, is_default TINYINT(1) NOT NULL,'
                    . ' PRIMARY KEY(id))',
                    $expectedTableName
                ),
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                $expectedClassName => [
                    'configs' => [
                        'entity' => [
                            'label' => 'oro.entityextend.enums.test_status.entity_label',
                            'plural_label' => 'oro.entityextend.enums.test_status.entity_plural_label',
                            'description' => 'oro.entityextend.enums.test_status.entity_description',
                        ],
                        'extend' => [
                            'owner' => ExtendScope::OWNER_SYSTEM,
                            'is_extend' => true,
                            'table' => 'oro_enum_test_status',
                            'inherit' => ExtendHelper::BASE_ENUM_VALUE_CLASS
                        ],
                        'enum' => [
                            'code' => 'test_status',
                            'public' => true,
                            'multiple' => true,
                            'immutable' => true,
                        ],
                        'test_scope' => [
                            'test_attr' => 'test'
                        ],
                    ],
                    'mode' => ConfigModel::MODE_HIDDEN,
                    'fields' => [
                        'id' => [
                            'configs' => [
                                'entity' => [
                                    'label' => 'oro.entityextend.enumvalue.id.label',
                                    'description' => 'oro.entityextend.enumvalue.id.description',
                                ],
                                'importexport' => [
                                    'identity' => true,
                                ],
                                'extend' => [
                                    'length' => 32,
                                    'nullable' => false
                                ],
                            ],
                            'type' => 'string',
                        ],
                        'name' => [
                            'configs' => [
                                'entity' => [
                                    'label' => 'oro.entityextend.enumvalue.name.label',
                                    'description' => 'oro.entityextend.enumvalue.name.description',
                                ],
                                'datagrid' => [
                                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                                ],
                                'extend' => [
                                    'length' => 255,
                                ],
                                'importexport' => [
                                    'identity' => false,
                                ],
                            ],
                            'type' => 'string',
                        ],
                        'priority' => [
                            'configs' => [
                                'entity' => [
                                    'label' => 'oro.entityextend.enumvalue.priority.label',
                                    'description' => 'oro.entityextend.enumvalue.priority.description',
                                ],
                                'datagrid' => [
                                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                                ]
                            ],
                            'type' => 'integer',
                        ],
                        'default' => [
                            'configs' => [
                                'entity' => [
                                    'label' => 'oro.entityextend.enumvalue.default.label',
                                    'description' => 'oro.entityextend.enumvalue.default.description',
                                ],
                                'datagrid' => [
                                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                                ]
                            ],
                            'type' => 'boolean',
                        ],
                    ]
                ],
            ]
        );
    }

    public function testAddEnumFieldForExistingEnum()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $enumCode = 'test_enum';
        $enumTableName = ExtendDbIdentifierNameGenerator::ENUM_TABLE_PREFIX . $enumCode;
        $enumClassName = ExtendHelper::ENTITY_NAMESPACE . 'EV_Test_Enum';

        $enumTable = $schema->createTable($enumTableName);
        $enumTable->addColumn('id', 'string', ['length' => 32]);
        $enumTable->addColumn('name', 'string');
        $enumTable->setPrimaryKey(['id']);

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $extension->addEnumField(
            $schema,
            $table1,
            'enum1',
            $enumCode
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE oro_enum_test_enum ('
                . 'id VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table1 (id INT NOT NULL, enum1_id VARCHAR(32) DEFAULT NULL, '
                . 'INDEX idx_table1_enum1_id (enum1_id), PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_table1_enum1_id '
                . 'FOREIGN KEY (enum1_id) REFERENCES oro_enum_test_enum (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'enum1' => [
                            'type' => 'enum',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_SYSTEM,
                                    'target_entity' => $enumClassName,
                                    'target_field' => 'name',
                                    'bidirectional' => false,
                                    'relation_key' =>
                                        'manyToOne|Acme\AcmeBundle\Entity\Entity1|' . $enumClassName . '|enum1',
                                ],
                                'enum' => [
                                    'enum_code' => $enumCode
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddEnumFieldMultipleForExistingEnum()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $enumCode = 'test_enum';
        $enumTableName = ExtendDbIdentifierNameGenerator::ENUM_TABLE_PREFIX . $enumCode;
        $enumClassName = ExtendHelper::ENTITY_NAMESPACE . 'EV_Test_Enum';

        $enumTable = $schema->createTable($enumTableName);
        $enumTable->addColumn('id', 'string', ['length' => 32]);
        $enumTable->addColumn('name', 'string');
        $enumTable->setPrimaryKey(['id']);

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $extension->addEnumField(
            $schema,
            $table1,
            'enum1',
            $enumCode,
            true
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE oro_enum_test_enum ('
                . 'id VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table1 (id INT NOT NULL, '
                . 'enum1' . ExtendDbIdentifierNameGenerator::SNAPSHOT_COLUMN_SUFFIX . ' VARCHAR(500) DEFAULT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE oro_rel_f061705c40c71809fbe307 ('
                . 'entity1_id INT NOT NULL, ev_test_enum_id VARCHAR(32) NOT NULL, '
                . 'INDEX IDX_A8A92398C33725A7 (entity1_id), '
                . 'INDEX IDX_A8A923982ACB7ECA (ev_test_enum_id), '
                . 'PRIMARY KEY(entity1_id, ev_test_enum_id))',
                'ALTER TABLE oro_rel_f061705c40c71809fbe307 ADD CONSTRAINT FK_A8A92398C33725A7 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE oro_rel_f061705c40c71809fbe307 ADD CONSTRAINT FK_A8A923982ACB7ECA '
                . 'FOREIGN KEY (ev_test_enum_id) REFERENCES oro_enum_test_enum (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'enum1' => [
                            'type' => 'multiEnum',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_SYSTEM,
                                    'without_default' => true,
                                    'target_entity' => $enumClassName,
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => false,
                                    'relation_key' =>
                                        'manyToMany|Acme\AcmeBundle\Entity\Entity1|' . $enumClassName . '|enum1',
                                ],
                                'enum' => [
                                    'enum_code' => $enumCode
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddOneToManyRelationWithNoPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('The table "table1" must have a primary key.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddOneToManyRelationWithCombinedPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('A primary key of "table1" table must include only one column.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->addColumn('id1', 'integer');
        $table1->setPrimaryKey(['id', 'id1']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddOneToManyRelationWithNoTargetPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('The table "table2" must have a primary key.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddOneToManyRelationWithCombinedTargetPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('A primary key of "table2" table must include only one column.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('id1', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id', 'id1']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddOneToManyRelationWithNoOptions()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id SMALLINT DEFAULT NULL, '
                . 'INDEX IDX_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX IDX_859C7327B0E6CF0B (entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE table2 ADD CONSTRAINT FK_859C7327B0E6CF0B '
                . 'FOREIGN KEY (entity1_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'oneToMany',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_SYSTEM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => true,
                                    'relation_key' =>
                                        'oneToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddOneToManyRelation()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('name', 'string');
        $targetTable->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $selfTable,
            'relation_column1',
            'table2',
            ['name'],
            ['name'],
            ['name'],
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id SMALLINT DEFAULT NULL, '
                . 'INDEX IDX_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX IDX_859C7327B0E6CF0B (entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE table2 ADD CONSTRAINT FK_859C7327B0E6CF0B '
                . 'FOREIGN KEY (entity1_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'oneToMany',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => true,
                                    'relation_key' =>
                                        'oneToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddOneToManyRelationWhenOwningAndTargetEntitiesAreSame()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $table,
            'relation_column1',
            $table,
            ['name'],
            ['name'],
            ['name'],
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id INT DEFAULT NULL, '
                . 'entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX IDX_1C95229D63A7B402 (default_relation_column1_id), '
                . 'INDEX IDX_1C95229DB0E6CF0B (entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229DB0E6CF0B '
                . 'FOREIGN KEY (entity1_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'oneToMany',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity1',
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => true,
                                    'relation_key' =>
                                        'oneToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity1|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddOneToManyRelationWithoutDefaultForeignKey()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('name', 'string');
        $targetTable->setPrimaryKey(['id']);

        $extension->addOneToManyRelation(
            $schema,
            $selfTable,
            'relation_column1',
            'table2',
            ['name'],
            ['name'],
            ['name'],
            [
                'extend' => ['without_default' => true]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'entity1_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX IDX_859C7327B0E6CF0B (entity1_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'ALTER TABLE table2 ADD CONSTRAINT FK_859C7327B0E6CF0B '
                . 'FOREIGN KEY (entity1_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'oneToMany',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_SYSTEM,
                                    'without_default' => true,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => true,
                                    'relation_key' =>
                                        'oneToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddOneToManyInverseRelationValidateTitleColumnName()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("There is no column with name 'title' on table 'table1'.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('title', 'string');
        $targetTable->setPrimaryKey(['id']);

        $targetTable->addColumn('entity1_rooms_id', 'integer');
        $targetTable->addForeignKeyConstraint($selfTable, ['entity1_rooms_id'], ['id']);

        $extension->addOneToManyInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'user',
            'title',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    public function testAddOneToManyInverseRelation()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->addColumn('name', 'string');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('name', 'string');
        $targetTable->setPrimaryKey(['id']);

        $targetTable->addColumn('entity1_rooms_id', 'integer');
        $targetTable->addForeignKeyConstraint($selfTable, ['entity1_rooms_id'], ['id']);

        $extension->addOneToManyInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'user',
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );

        $relationKey = 'oneToMany|Acme\AcmeBundle\Entity\Entity1|Acme\AcmeBundle\Entity\Entity2|rooms';
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $relationKey . '.target_field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity2',
                                'user',
                                'manyToOne'
                            )
                        ]
                    ]
                ],
                'Acme\AcmeBundle\Entity\Entity2' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $relationKey . '.field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity2',
                                'user',
                                'manyToOne'
                            )
                        ]
                    ],
                    'fields' => [
                        'user' => [
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'column_name' => 'entity1_rooms_id',
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity1',
                                    'relation_key' => $relationKey,
                                    'bidirectional' => false,
                                    'target_field' => 'name'
                                ]
                            ],
                            'type' => 'manyToOne',
                            'mode' => 'readonly'
                        ]
                    ]
                ],
            ]
        );
    }

    public function testAddOneToManyInverseRelationWhenOwningAndTargetEntitiesAreSame()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);
        $table->addColumn('entity1_selfRel_id', 'integer');
        $table->addForeignKeyConstraint($table, ['entity1_selfRel_id'], ['id']);

        $extension->addOneToManyInverseRelation(
            $schema,
            $table,
            'selfRel',
            $table,
            'user',
            'name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );

        $selfRelationKey = 'oneToMany|Acme\AcmeBundle\Entity\Entity1|Acme\AcmeBundle\Entity\Entity1|selfRel';
        $targetRelationKey = $selfRelationKey . '|inverse';
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $selfRelationKey . '.target_field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity1',
                                'user',
                                'manyToOne'
                            ),
                            'relation.' . $targetRelationKey . '.field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity1',
                                'user',
                                'manyToOne'
                            )
                        ]
                    ],
                    'fields' => [
                        'user' => [
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'column_name' => 'entity1_selfRel_id',
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity1',
                                    'relation_key' => $targetRelationKey,
                                    'target_field' => 'name',
                                    'bidirectional' => false,
                                ]
                            ],
                            'type' => 'manyToOne',
                            'mode' => 'readonly'
                        ]
                    ]
                ]
            ]
        );
    }

    public function testInvalidRelationColumnType()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage(
            'The type of relation column "table1::rel_id" must be an integer or string. "float" type is not supported.'
        );

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'float');
        $targetTable->setPrimaryKey(['id']);

        $extension->addManyToOneRelation(
            $schema,
            $selfTable,
            'rel',
            $targetTable,
            'id'
        );
    }

    public function testCheckColumnsExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one column must be specified.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('name', 'string');
        $targetTable->setPrimaryKey(['name']);

        $extension->addOneToManyRelation(
            $schema,
            $selfTable,
            'relation_column1',
            $targetTable,
            ['name'],
            [],
            ['name']
        );
    }

    public function testAddManyToManyRelationWithNoPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('The table "table1" must have a primary key.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddManyToManyRelationWithCombinedPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('A primary key of "table1" table must include only one column.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->addColumn('id1', 'integer');
        $table1->setPrimaryKey(['id', 'id1']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddManyToManyRelationWithNoTargetPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('The table "table2" must have a primary key.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddManyToManyRelationWithCombinedTargetPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('A primary key of "table2" table must include only one column.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('id1', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id', 'id1']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddManyToManyRelationWithNoOptions()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name']
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id SMALLINT DEFAULT NULL, '
                . 'INDEX IDX_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE oro_rel_f061705960f46bf2d67f27 ('
                . 'entity1_id INT NOT NULL, '
                . 'entity2_id SMALLINT NOT NULL, '
                . 'INDEX IDX_8CE090DAC33725A7 (entity1_id), '
                . 'INDEX IDX_8CE090DAD1828A49 (entity2_id), '
                . 'PRIMARY KEY(entity1_id, entity2_id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAC33725A7 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAD1828A49 '
                . 'FOREIGN KEY (entity2_id) REFERENCES table2 (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'manyToMany',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_SYSTEM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => false,
                                    'relation_key' =>
                                        'manyToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddManyToManyRelation()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id SMALLINT DEFAULT NULL, '
                . 'INDEX IDX_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE oro_rel_f061705960f46bf2d67f27 ('
                . 'entity1_id INT NOT NULL, '
                . 'entity2_id SMALLINT NOT NULL, '
                . 'INDEX IDX_8CE090DAC33725A7 (entity1_id), '
                . 'INDEX IDX_8CE090DAD1828A49 (entity2_id), '
                . 'PRIMARY KEY(entity1_id, entity2_id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAC33725A7 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAD1828A49 '
                . 'FOREIGN KEY (entity2_id) REFERENCES table2 (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'manyToMany',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => false,
                                    'relation_key' =>
                                        'manyToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddManyToManyRelationWithoutDefaultForeignKey()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            ['name'],
            ['name'],
            ['name'],
            [
                'extend' => ['without_default' => true]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE table2 ('
                . 'id SMALLINT NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE oro_rel_f061705960f46bf2d67f27 ('
                . 'entity1_id INT NOT NULL, '
                . 'entity2_id SMALLINT NOT NULL, '
                . 'INDEX IDX_8CE090DAC33725A7 (entity1_id), '
                . 'INDEX IDX_8CE090DAD1828A49 (entity2_id), '
                . 'PRIMARY KEY(entity1_id, entity2_id))',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAC33725A7 '
                . 'FOREIGN KEY (entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE oro_rel_f061705960f46bf2d67f27 ADD CONSTRAINT FK_8CE090DAD1828A49 '
                . 'FOREIGN KEY (entity2_id) REFERENCES table2 (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'manyToMany',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_SYSTEM,
                                    'without_default' => true,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => false,
                                    'relation_key' =>
                                        'manyToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddManyToManyRelationWhenOwningAndTargetEntitiesAreSame()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);

        $extension->addManyToManyRelation(
            $schema,
            $table,
            'relation_column1',
            $table,
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'default_relation_column1_id INT DEFAULT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'INDEX IDX_1C95229D63A7B402 (default_relation_column1_id), '
                . 'PRIMARY KEY(id))',
                'CREATE TABLE oro_rel_f061705f0617052d67f27a ('
                . 'src_entity1_id INT NOT NULL, '
                . 'dest_entity1_id INT NOT NULL, '
                . 'INDEX IDX_CECEE5B692AFC5D (src_entity1_id), '
                . 'INDEX IDX_CECEE5B6FC660CD1 (dest_entity1_id), '
                . 'PRIMARY KEY(src_entity1_id, dest_entity1_id))',
                'ALTER TABLE table1 ADD CONSTRAINT FK_1C95229D63A7B402 '
                . 'FOREIGN KEY (default_relation_column1_id) REFERENCES table1 (id) ON DELETE SET NULL',
                'ALTER TABLE oro_rel_f061705f0617052d67f27a ADD CONSTRAINT FK_CECEE5B692AFC5D '
                . 'FOREIGN KEY (src_entity1_id) REFERENCES table1 (id) ON DELETE CASCADE',
                'ALTER TABLE oro_rel_f061705f0617052d67f27a ADD CONSTRAINT FK_CECEE5B6FC660CD1 '
                . 'FOREIGN KEY (dest_entity1_id) REFERENCES table1 (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'manyToMany',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity1',
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'bidirectional' => false,
                                    'relation_key' =>
                                        'manyToMany|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity1|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddManyToManyInverseRelationValidateTitleColumn()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("There is no column with name 'title' on table 'table1'.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('title', 'string');
        $targetTable->addColumn('detailed', 'string');
        $targetTable->addColumn('grid', 'string');
        $targetTable->setPrimaryKey(['id']);

        $extension->addManyToManyInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'users',
            ['title'],
            ['detailed'],
            ['grid'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    public function testAddManyToManyInverseRelationValidateDetailedColumn()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("There is no column with name 'detailed' on table 'table1'.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->addColumn('title', 'string');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('title', 'string');
        $targetTable->addColumn('detailed', 'string');
        $targetTable->addColumn('grid', 'string');
        $targetTable->setPrimaryKey(['id']);

        $extension->addManyToManyInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'users',
            ['title'],
            ['detailed'],
            ['grid'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    public function testAddManyToManyInverseRelationValidateGridColumn()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("There is no column with name 'grid' on table 'table1'.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->addColumn('title', 'string');
        $selfTable->addColumn('detailed', 'string');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('title', 'string');
        $targetTable->addColumn('detailed', 'string');
        $targetTable->addColumn('grid', 'string');
        $targetTable->setPrimaryKey(['id']);

        $extension->addManyToManyInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'users',
            ['title'],
            ['detailed'],
            ['grid'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    public function testAddManyToManyInverseRelation()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->addColumn('name', 'string');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('name', 'string');
        $targetTable->setPrimaryKey(['id']);

        $joinTable = $schema->createTable('oro_rel_f061705960f46bf2d67f27');
        $joinTable->addColumn('entity1_id', 'integer');
        $joinTable->addForeignKeyConstraint($selfTable, ['entity1_id'], ['id']);
        $joinTable->addColumn('entity2_id', 'integer');
        $joinTable->addForeignKeyConstraint($targetTable, ['entity2_id'], ['id']);
        $joinTable->setPrimaryKey(['entity1_id', 'entity2_id']);

        $this->entityMetadataHelper->expects($this->once())
            ->method('isEntityClassContainsColumn')
            ->with('Acme\AcmeBundle\Entity\Entity1', 'rooms')
            ->willReturn(true);

        $extension->addManyToManyInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'users',
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );

        $relationKey = 'manyToMany|Acme\AcmeBundle\Entity\Entity1|Acme\AcmeBundle\Entity\Entity2|rooms';
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $relationKey . '.target_field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity2',
                                'users',
                                'manyToMany'
                            )
                        ]
                    ],
                    'fields' => [
                        'rooms' => [
                            'configs' => [
                                'extend' => [
                                    'bidirectional' => true
                                ],
                            ],
                        ],
                    ],
                ],
                'Acme\AcmeBundle\Entity\Entity2' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $relationKey . '.field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity2',
                                'users',
                                'manyToMany'
                            )
                        ]
                    ],
                    'fields' => [
                        'users' => [
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity1',
                                    'relation_key' => $relationKey,
                                    'bidirectional' => false,
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name']
                                ]
                            ],
                            'type' => 'manyToMany',
                            'mode' => 'readonly'
                        ]
                    ]
                ],
            ]
        );
    }

    public function testAddManyToManyInverseRelationWhenOwningAndTargetEntitiesAreSame()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);

        $extension->addManyToManyInverseRelation(
            $schema,
            $table,
            'selfRel',
            $table,
            'users',
            ['name'],
            ['name'],
            ['name'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );

        $this->entityMetadataHelper->expects($this->once())
            ->method('isEntityClassContainsColumn')
            ->with('Acme\AcmeBundle\Entity\Entity1', 'selfRel')
            ->willReturn(true);

        $selfRelationKey = 'manyToMany|Acme\AcmeBundle\Entity\Entity1|Acme\AcmeBundle\Entity\Entity1|selfRel';
        $targetRelationKey = $selfRelationKey . '|inverse';
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $selfRelationKey . '.target_field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity1',
                                'users',
                                'manyToMany'
                            ),
                            'relation.' . $targetRelationKey . '.field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity1',
                                'users',
                                'manyToMany'
                            )
                        ]
                    ],
                    'fields' => [
                        'selfRel' => [
                            'configs' => [
                                'extend' => [
                                    'bidirectional' => true
                                ],
                            ],
                        ],
                        'users' => [
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity1',
                                    'relation_key' => $targetRelationKey,
                                    'bidirectional' => false,
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name']
                                ]
                            ],
                            'type' => 'manyToMany',
                            'mode' => 'readonly'
                        ]
                    ]
                ],
            ]
        );
    }

    public function testAddManyToOneRelationWithNoTargetPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('The table "table2" must have a primary key.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('name', 'string');

        $extension->addManyToOneRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            'name'
        );
    }

    public function testAddManyToOneRelationWithCombinedTargetPrimaryKey()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('A primary key of "table2" table must include only one column.');

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'smallint');
        $table2->addColumn('id1', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id', 'id1']);

        $extension->addManyToOneRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            'name'
        );
    }

    public function testAddManyToOneRelationWithNoOptions()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToOneRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            'name'
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'relation_column1_id INT DEFAULT NULL, '
                . 'INDEX idx_table1_relation_column1_id (relation_column1_id), PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_table1_relation_column1_id '
                . 'FOREIGN KEY (relation_column1_id) REFERENCES table2 (id) ON DELETE SET NULL'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'manyToOne',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_SYSTEM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_field' => 'name',
                                    'bidirectional' => false,
                                    'relation_key' =>
                                        'manyToOne|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddManyToOneRelation()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table1 = $schema->createTable('table1');
        $table1->addColumn('id', 'integer');
        $table1->setPrimaryKey(['id']);

        $table2 = $schema->createTable('table2');
        $table2->addColumn('id', 'integer');
        $table2->addColumn('name', 'string');
        $table2->setPrimaryKey(['id']);

        $extension->addManyToOneRelation(
            $schema,
            $table1,
            'relation_column1',
            $table2,
            'name',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'on_delete' => 'CASCADE',
                    'nullable' => false
                ]
            ]
        );

        $this->assertSchemaSql(
            $schema,
            [
                'CREATE TABLE table1 ('
                . 'id INT NOT NULL, '
                . 'relation_column1_id INT NOT NULL, '
                . 'INDEX idx_table1_relation_column1_id (relation_column1_id), PRIMARY KEY(id))',
                'CREATE TABLE table2 (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
                'ALTER TABLE table1 ADD CONSTRAINT fk_table1_relation_column1_id '
                . 'FOREIGN KEY (relation_column1_id) REFERENCES table2 (id) ON DELETE CASCADE'
            ]
        );
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'fields' => [
                        'relation_column1' => [
                            'type' => 'manyToOne',
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity2',
                                    'target_field' => 'name',
                                    'bidirectional' => false,
                                    'relation_key' =>
                                        'manyToOne|Acme\AcmeBundle\Entity\Entity1|'
                                        . 'Acme\AcmeBundle\Entity\Entity2|relation_column1',
                                    'on_delete' => 'CASCADE',
                                    'nullable' => false
                                ]
                            ],
                            'mode' => 'readonly'
                        ]
                    ],
                ],
            ]
        );
    }

    public function testAddManyToOneInverseRelationValidateTitleColumn()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("There is no column with name 'title' on table 'table1'.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('title', 'string');
        $targetTable->addColumn('detailed', 'string');
        $targetTable->addColumn('grid', 'string');
        $targetTable->setPrimaryKey(['id']);

        $extension->addManyToOneInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'users',
            ['title'],
            ['detailed'],
            ['grid'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    public function testAddManyToOneInverseRelationValidateDetailedColumn()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("There is no column with name 'detailed' on table 'table1'.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->addColumn('title', 'string');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('title', 'string');
        $targetTable->addColumn('detailed', 'string');
        $targetTable->addColumn('grid', 'string');
        $targetTable->setPrimaryKey(['id']);

        $extension->addManyToOneInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'users',
            ['title'],
            ['detailed'],
            ['grid'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    public function testAddManyToOneInverseRelationValidateGridColumn()
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("There is no column with name 'grid' on table 'table1'.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->addColumn('title', 'string');
        $selfTable->addColumn('detailed', 'string');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('title', 'string');
        $targetTable->addColumn('detailed', 'string');
        $targetTable->addColumn('grid', 'string');
        $targetTable->setPrimaryKey(['id']);

        $extension->addManyToOneInverseRelation(
            $schema,
            $selfTable,
            'rooms',
            'table2',
            'users',
            ['title'],
            ['detailed'],
            ['grid'],
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddManyToOneInverseRelation()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $selfTable = $schema->createTable('table1');
        $selfTable->addColumn('id', 'integer');
        $selfTable->addColumn('name', 'string');
        $selfTable->setPrimaryKey(['id']);

        $targetTable = $schema->createTable('table2');
        $targetTable->addColumn('id', 'smallint');
        $targetTable->addColumn('name', 'string');
        $targetTable->setPrimaryKey(['id']);

        $selfTable->addColumn('room_id', 'integer');
        $selfTable->addForeignKeyConstraint($targetTable, ['room_id'], ['id']);

        $extension->addManyToOneInverseRelation(
            $schema,
            $selfTable,
            'room',
            'table2',
            'users',
            ['name'],
            ['name'],
            ['name'],
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'on_delete' => 'CASCADE'
                ]
            ]
        );

        $this->entityMetadataHelper->expects($this->once())
            ->method('isEntityClassContainsColumn')
            ->with('Acme\AcmeBundle\Entity\Entity1', 'room')
            ->willReturn(true);

        $relationKey = 'manyToOne|Acme\AcmeBundle\Entity\Entity1|Acme\AcmeBundle\Entity\Entity2|room';
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $relationKey . '.target_field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity2',
                                'users',
                                'oneToMany'
                            ),
                            'relation.' . $relationKey . '.on_delete' => 'CASCADE'
                        ],

                    ],
                    'fields' => [
                        'room' => [
                            'configs' => [
                                'extend' => [
                                    'bidirectional' => true
                                ],
                            ],
                        ],
                    ],
                ],
                'Acme\AcmeBundle\Entity\Entity2' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $relationKey . '.field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity2',
                                'users',
                                'oneToMany'
                            )
                        ]
                    ],
                    'fields' => [
                        'users' => [
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity1',
                                    'relation_key' => $relationKey,
                                    'bidirectional' => false,
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'on_delete' => 'CASCADE'
                                ]
                            ],
                            'type' => 'oneToMany',
                            'mode' => 'readonly'
                        ]
                    ]
                ],
            ]
        );
    }

    public function testAddManyToOneInverseRelationWhenOwningAndTargetEntitiesAreSame()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);

        $this->entityMetadataHelper->expects($this->once())
            ->method('isEntityClassContainsColumn')
            ->with('Acme\AcmeBundle\Entity\Entity1', 'selfRel')
            ->willReturn(true);

        $extension->addManyToOneInverseRelation(
            $schema,
            $table,
            'selfRel',
            $table,
            'users',
            ['name'],
            ['name'],
            ['name'],
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'orphanRemoval' => true
                ]
            ]
        );

        $selfRelationKey = 'manyToOne|Acme\AcmeBundle\Entity\Entity1|Acme\AcmeBundle\Entity\Entity1|selfRel';
        $targetRelationKey = $selfRelationKey . '|inverse';
        $this->assertExtendOptions(
            $schema,
            [
                'Acme\AcmeBundle\Entity\Entity1' => [
                    'configs' => [
                        'extend' => [
                            'relation.' . $selfRelationKey . '.target_field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity1',
                                'users',
                                'oneToMany'
                            ),
                            'relation.' . $targetRelationKey . '.field_id' => new FieldConfigId(
                                'extend',
                                'Acme\AcmeBundle\Entity\Entity1',
                                'users',
                                'oneToMany'
                            ),
                            'relation.' . $selfRelationKey . '.on_delete' => 'SET NULL',
                            'relation.' . $targetRelationKey . '.orphanRemoval' => true
                        ]
                    ],
                    'fields' => [
                        'selfRel' => [
                            'configs' => [
                                'extend' => [
                                    'bidirectional' => true
                                ],
                            ],
                        ],
                        'users' => [
                            'configs' => [
                                'extend' => [
                                    'is_extend' => true,
                                    'owner' => ExtendScope::OWNER_CUSTOM,
                                    'target_entity' => 'Acme\AcmeBundle\Entity\Entity1',
                                    'relation_key' => $targetRelationKey,
                                    'bidirectional' => false,
                                    'target_title' => ['name'],
                                    'target_detailed' => ['name'],
                                    'target_grid' => ['name'],
                                    'orphanRemoval' => true
                                ]
                            ],
                            'type' => 'oneToMany',
                            'mode' => 'readonly'
                        ]
                    ]
                ],
            ]
        );
    }

    public function testAddManyToOneInverseRelationWhenFieldIsHidden()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Target field can't be hidden.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $this->extendOptionsManager
            ->setColumnOptions(
                'oro_test',
                'OroTestRelation',
                [ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN]
            );

        $extension->addManyToOneInverseRelation(
            $schema,
            'oro_test',
            'OroTestRelation',
            'oro_test2',
            'users',
            ['name'],
            ['name'],
            ['name']
        );
    }

    public function testAddOneToManyInverseRelationWhenFieldIsHidden()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Target field can't be hidden.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $this->extendOptionsManager
            ->setColumnOptions(
                'oro_test',
                'OroTestRelation',
                [ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN]
            );

        $extension->addOneToManyInverseRelation(
            $schema,
            'oro_test',
            'OroTestRelation',
            'oro_test2',
            'users',
            'name',
            ['name']
        );
    }

    public function testAddManyToManyInverseRelationWhenFieldIsHidden()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Target field can't be hidden.");

        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension();

        $this->extendOptionsManager
            ->setColumnOptions(
                'oro_test',
                'OroTestRelation',
                [ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN]
            );

        $extension->addManyToManyInverseRelation(
            $schema,
            'oro_test',
            'OroTestRelation',
            'oro_test2',
            'users',
            ['name'],
            ['name'],
            ['name']
        );
    }

    /**
     * @dataProvider validateOptionsDataProvider
     */
    public function testValidateOptionAllowedTypesInManyToManyRelation(array $config, bool $throwException)
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension($config);

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);

        if ($throwException) {
            $this->expectException(\UnexpectedValueException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $extension->addManyToManyRelation(
            $schema,
            $table,
            'associationName',
            'table1',
            ['id'],
            ['id'],
            ['id'],
            [
                'scope' => [
                    'not_allowed_option' => true
                ],
            ]
        );
    }

    /**
     * @dataProvider validateOptionsDataProvider
     */
    public function testValidateOptionAllowedTypesInOneToManyRelation(array $config, bool $throwException)
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension($config);

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);

        if ($throwException) {
            $this->expectException(\UnexpectedValueException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $extension->addOneToManyRelation(
            $schema,
            $table,
            'associationName',
            'table1',
            ['id'],
            ['id'],
            ['id'],
            [
                'scope' => [
                    'not_allowed_option' => true
                ],
            ]
        );
    }

    /**
     * @dataProvider validateOptionsDataProvider
     */
    public function testValidateOptionAllowedTypesInManyToOneRelation(array $config, bool $throwException)
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension($config);

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);

        if ($throwException) {
            $this->expectException(\UnexpectedValueException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $extension->addManyToOneRelation(
            $schema,
            $table,
            'associationName',
            'table1',
            'id',
            [
                'scope' => [
                    'not_allowed_option' => true
                ],
            ]
        );
    }

    public function testValidationWillNotBreakOnInvalidOptionProvided()
    {
        $schema = $this->getExtendSchema();
        $extension = $this->getExtendExtension([
            'scope' => ['options' => []]
        ]);

        $table = $schema->createTable('table1');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'string');
        $table->setPrimaryKey(['id']);

        $extension->addManyToOneRelation(
            $schema,
            $table,
            'associationName',
            'table1',
            'id',
            [
                '_custom_option' => true,
                'scope' => [
                    'not_allowed_option' => true
                ],
            ]
        );

        $this->assertArrayHasKey('table1!associationName', $schema->getExtendOptions());
    }

    public function validateOptionsDataProvider(): array
    {
        return [
            'config with not allowed option ' => [
                [
                    'scope' => [
                        'field' => [
                            'items' => [
                                'not_allowed_option' => [
                                    'options' => [
                                        'allowed_type' => ['allowed_one', 'allowed_two']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                true
            ],
            'config with allowed option' => [
                [
                    'scope' => [
                        'field' => [
                            'items' => [
                                'not_allowed_option' => [
                                    'options' => [
                                        'allowed_type' => ['oneToMany', 'manyToOne', 'manyToMany']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                false
            ]
        ];
    }

    private function assertSchemaSql(Schema $schema, array $expectedSql)
    {
        $sql = $schema->toSql(new MySqlPlatform());
        foreach ($sql as &$el) {
            $el = str_replace(
                ' DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB',
                '',
                $el
            );
        }
        $this->assertEquals($expectedSql, $sql);
    }

    private function assertExtendOptions(ExtendSchema $schema, array $expectedOptions)
    {
        $extendOptions = $schema->getExtendOptions();
        $extendOptions = $this->extendOptionsParser->parseOptions($extendOptions);
        $this->assertEquals($expectedOptions, $extendOptions);
    }
}
