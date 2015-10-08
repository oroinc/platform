<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\EnumEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumEntityConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $relationBuilder;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var EnumEntityConfigDumperExtension */
    protected $extension;

    public function setUp()
    {
        $this->configManager   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->relationBuilder = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->nameGenerator   = new ExtendDbIdentifierNameGenerator();

        $this->extension = new EnumEntityConfigDumperExtension(
            $this->configManager,
            $this->relationBuilder,
            new FieldTypeHelper(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany']),
            $this->nameGenerator
        );
    }

    public function testSupportsPreUpdate()
    {
        $this->assertTrue(
            $this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    public function testSupportsPostUpdate()
    {
        $this->assertTrue(
            $this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPreUpdate()
    {
        $entityConfig1 = new Config(new EntityConfigId('extend', 'Test\EnumValue1'));
        $entityConfig1->set('is_extend', true);
        $entityConfig2 = new Config(new EntityConfigId('extend', 'Test\EnumValue2'));

        $fieldConfig1 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field1', 'enum'));
        $fieldConfig1->set('state', ExtendScope::STATE_NEW);
        $fieldConfig2 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field2', 'multiEnum'));
        $fieldConfig2->set('state', ExtendScope::STATE_UPDATE);
        $fieldConfig3 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field3', 'enum'));
        $fieldConfig3->set('state', ExtendScope::STATE_DELETE);
        $fieldConfig4 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field4', 'manyToOne'));
        $fieldConfig4->set('state', ExtendScope::STATE_NEW);
        $fieldConfig5 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field5', 'enum'));
        $fieldConfig5->set('state', ExtendScope::STATE_NEW);

        $enumFieldConfig1 = new Config(new FieldConfigId('enum', 'Test\EnumValue1', 'field1', 'enum'));
        $enumFieldConfig1->set('enum_name', 'Test Enum 1');
        $enumFieldConfig1->set('enum_public', true);
        $enumFieldConfig2 = new Config(new FieldConfigId('enum', 'Test\EnumValue1', 'field2', 'enum'));
        $enumFieldConfig2->set('enum_name', 'Test Enum 2');
        $enumFieldConfig2->set('enum_public', true);
        $enumFieldConfig5 = new Config(new FieldConfigId('enum', 'Test\EnumValue1', 'field5', 'enum'));

        $entityConfigs = [$entityConfig1, $entityConfig2];
        $fieldConfigs  = [$fieldConfig1, $fieldConfig2, $fieldConfig3, $fieldConfig4, $fieldConfig5];

        $enumCode1           = ExtendHelper::buildEnumCode('Test Enum 1');
        $enumCode2           = ExtendHelper::buildEnumCode('Test Enum 2');
        $enumCode5           = ExtendHelper::generateEnumCode('Test\EnumValue1', 'field5');
        $enumValueClassName1 = ExtendHelper::buildEnumValueClassName($enumCode1);
        $enumValueClassName2 = ExtendHelper::buildEnumValueClassName($enumCode2);
        $enumValueClassName5 = ExtendHelper::buildEnumValueClassName($enumCode5);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $enumConfigProvider   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['enum', $enumConfigProvider],
                    ]
                )
            );
        $extendConfigProvider->expects($this->at(0))
            ->method('getConfigs')
            ->will($this->returnValue($entityConfigs));
        $extendConfigProvider->expects($this->at(1))
            ->method('getConfigs')
            ->with($entityConfig1->getId()->getClassName())
            ->will($this->returnValue($fieldConfigs));
        $enumConfigProvider->expects($this->at(0))
            ->method('getConfig')
            ->with($entityConfig1->getId()->getClassName(), 'field1')
            ->will($this->returnValue($enumFieldConfig1));
        $enumConfigProvider->expects($this->at(1))
            ->method('getConfig')
            ->with($entityConfig1->getId()->getClassName(), 'field2')
            ->will($this->returnValue($enumFieldConfig2));
        $enumConfigProvider->expects($this->at(2))
            ->method('getConfig')
            ->with($entityConfig1->getId()->getClassName(), 'field5')
            ->will($this->returnValue($enumFieldConfig5));

        $this->configManager->expects($this->exactly(3))
            ->method('hasConfigEntityModel')
            ->will(
                $this->returnValueMap(
                    [
                        [$enumValueClassName1, false],
                        [$enumValueClassName2, true],
                        [$enumValueClassName5, true],
                    ]
                )
            );

        $configManagerAt = 3;
        $this->configManager->expects($this->at($configManagerAt++))
            ->method('createConfigEntityModel')
            ->with($enumValueClassName1, ConfigModel::MODE_HIDDEN);

        $relationBuilderAt = 0;
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('updateEntityConfigs')
            ->with(
                $enumValueClassName1,
                [
                    'entity'     => [
                        'label'        => ExtendHelper::getEnumTranslationKey('label', $enumCode1),
                        'plural_label' => ExtendHelper::getEnumTranslationKey('plural_label', $enumCode1),
                        'description'  => ExtendHelper::getEnumTranslationKey('description', $enumCode1)
                    ],
                    'extend'     => [
                        'owner'     => ExtendScope::OWNER_SYSTEM,
                        'is_extend' => true,
                        'table'     => $this->nameGenerator->generateEnumTableName($enumCode1),
                        'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS
                    ],
                    'enum'       => [
                        'code'     => $enumCode1,
                        'public'   => true,
                        'multiple' => false
                    ]
                ]
            );
        $this->setAddEnumValueEntityFieldsExpectations(
            $enumValueClassName1,
            $enumCode1,
            $configManagerAt,
            $relationBuilderAt
        );
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($entityConfig1),
                $enumValueClassName1,
                'field1',
                'name',
                [
                    'enum'         => [
                        'enum_code' => $enumCode1
                    ]
                ],
                'enum'
            );
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('updateEntityConfigs')
            ->with(
                $enumValueClassName2,
                [
                    'enum' => [
                        'public' => true
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('addManyToManyRelation')
            ->with(
                $this->identicalTo($entityConfig1),
                $enumValueClassName2,
                'field2',
                ['name'],
                ['name'],
                ['name'],
                [
                    'enum'         => [
                        'enum_code' => $enumCode2
                    ],
                    'extend'       => [
                        'without_default' => true
                    ]
                ],
                'multiEnum'
            );
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($entityConfig1),
                $enumValueClassName5,
                'field5',
                'name',
                [
                    'enum'         => [
                        'enum_code' => $enumCode5
                    ]
                ],
                'enum'
            );

        $this->extension->preUpdate();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPreUpdateForNewEnumWithLongEnumCode()
    {
        $entityConfig1 = new Config(new EntityConfigId('extend', 'Test\EnumValue1'));
        $entityConfig1->set('is_extend', true);

        $fieldConfig1 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field1', 'enum'));
        $fieldConfig1->set('state', ExtendScope::STATE_NEW);

        $enumFieldConfig1 = new Config(new FieldConfigId('enum', 'Test\EnumValue1', 'field1', 'enum'));

        $entityConfigs = [$entityConfig1];
        $fieldConfigs  = [$fieldConfig1];

        $enumCode1           = ExtendHelper::generateEnumCode('Test\EnumValue1', 'field1');
        $enumValueClassName1 = ExtendHelper::buildEnumValueClassName($enumCode1);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $enumConfigProvider   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['enum', $enumConfigProvider],
                    ]
                )
            );
        $extendConfigProvider->expects($this->at(0))
            ->method('getConfigs')
            ->will($this->returnValue($entityConfigs));
        $extendConfigProvider->expects($this->at(1))
            ->method('getConfigs')
            ->with($entityConfig1->getId()->getClassName())
            ->will($this->returnValue($fieldConfigs));
        $enumConfigProvider->expects($this->at(0))
            ->method('getConfig')
            ->with($entityConfig1->getId()->getClassName(), 'field1')
            ->will($this->returnValue($enumFieldConfig1));

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')
            ->will(
                $this->returnValueMap(
                    [
                        [$enumValueClassName1, false],
                    ]
                )
            );

        $configManagerAt = 3;
        $this->configManager->expects($this->at($configManagerAt++))
            ->method('createConfigEntityModel')
            ->with($enumValueClassName1, ConfigModel::MODE_HIDDEN);

        $relationBuilderAt = 0;
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('updateEntityConfigs')
            ->with(
                $enumValueClassName1,
                [
                    'entity'     => [
                        'label'        => ExtendHelper::getEnumTranslationKey('label', $enumCode1),
                        'plural_label' => ExtendHelper::getEnumTranslationKey('plural_label', $enumCode1),
                        'description'  => ExtendHelper::getEnumTranslationKey('description', $enumCode1)
                    ],
                    'extend'     => [
                        'owner'     => ExtendScope::OWNER_SYSTEM,
                        'is_extend' => true,
                        'table'     => $this->nameGenerator->generateEnumTableName($enumCode1, true),
                        'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS
                    ],
                    'enum'       => [
                        'code'     => $enumCode1,
                        'public'   => false,
                        'multiple' => false
                    ]
                ]
            );
        $this->setAddEnumValueEntityFieldsExpectations(
            $enumValueClassName1,
            $enumCode1,
            $configManagerAt,
            $relationBuilderAt
        );
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($entityConfig1),
                $enumValueClassName1,
                'field1',
                'name',
                [
                    'enum'         => [
                        'enum_code' => $enumCode1
                    ]
                ],
                'enum'
            );

        $this->extension->preUpdate();
    }

    /**
     * @param $enumValueClassName
     * @param $enumCode
     * @param $configManagerAt
     * @param $relationBuilderAt
     */
    protected function setAddEnumValueEntityFieldsExpectations(
        $enumValueClassName,
        $enumCode,
        &$configManagerAt,
        &$relationBuilderAt
    ) {
        $this->configManager->expects($this->at($configManagerAt++))
            ->method('createConfigFieldModel')
            ->with($enumValueClassName, 'id', 'string');
        $this->configManager->expects($this->at($configManagerAt++))
            ->method('createConfigFieldModel')
            ->with($enumValueClassName, 'name', 'string');
        $this->configManager->expects($this->at($configManagerAt++))
            ->method('createConfigFieldModel')
            ->with($enumValueClassName, 'priority', 'integer');
        $this->configManager->expects($this->at($configManagerAt++))
            ->method('createConfigFieldModel')
            ->with($enumValueClassName, 'default', 'boolean');

        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('updateFieldConfigs')
            ->with(
                $enumValueClassName,
                'id',
                [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'id'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'id')
                    ],
                    'importexport' => [
                        'identity' => true
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('updateFieldConfigs')
            ->with(
                $enumValueClassName,
                'name',
                [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'name'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'name')
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('updateFieldConfigs')
            ->with(
                $enumValueClassName,
                'priority',
                [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'priority'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'priority')
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at($relationBuilderAt++))
            ->method('updateFieldConfigs')
            ->with(
                $enumValueClassName,
                'default',
                [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'default'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'default')
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ]
                ]
            );
    }

    public function testPostUpdateForEnumValues()
    {
        $entityConfig1 = new Config(new EntityConfigId('extend', 'Test\EnumValue1'));
        $entityConfig1->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $entityConfig2 = new Config(new EntityConfigId('extend', 'Test\EnumValue2'));
        $entityConfig2->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $entityConfig2->set(
            'schema',
            [
                'doctrine' => [
                    'Test\EnumValue2' => [
                        'repositoryClass' => 'Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository'
                    ]
                ]
            ]
        );

        $entityConfigs = [$entityConfig1, $entityConfig2];

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->will($this->returnValue($entityConfigs));

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entityConfig1));

        $this->extension->postUpdate();

        $this->assertEquals(
            [
                'doctrine' => [
                    'Test\EnumValue1' => [
                        'repositoryClass' => 'Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository',
                        'gedmo'           => [
                            'translation' => [
                                'entity' => 'Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation'
                            ]
                        ]
                    ]
                ]
            ],
            $entityConfig1->get('schema')
        );
    }

    public function testPostUpdateForMultiEnumFields()
    {
        $entityConfig1 = new Config(new EntityConfigId('extend', 'Test\EnumValue1'));
        $entityConfig1->set('is_extend', true);
        $entityConfig1->set('extend_class', 'Extend\EnumValue1');
        $entityConfig1->set(
            'schema',
            [
                'doctrine' => [
                    'Extend\EnumValue1' => [
                        'fields' => [
                            ExtendHelper::getMultiEnumSnapshotFieldName('field2') => [
                                'column' => 'field2'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $entityConfig2 = new Config(new EntityConfigId('extend', 'Test\EnumValue2'));

        $fieldConfig1 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field1', 'multiEnum'));
        $fieldConfig2 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field2', 'multiEnum'));
        $fieldConfig3 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field3', 'enum'));

        $entityConfigs = [$entityConfig1, $entityConfig2];
        $fieldConfigs  = [$fieldConfig1, $fieldConfig2, $fieldConfig3];

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->at(0))
            ->method('getConfigs')
            ->with(null, true)
            ->will($this->returnValue($entityConfigs));
        $extendConfigProvider->expects($this->at(1))
            ->method('getConfigs')
            ->with($entityConfig1->getId()->getClassName())
            ->will($this->returnValue($fieldConfigs));

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entityConfig1));

        $this->extension->postUpdate();

        $this->assertEquals(
            [
                'doctrine' => [
                    'Extend\EnumValue1' => [
                        'fields' => [
                            ExtendHelper::getMultiEnumSnapshotFieldName('field1') => [
                                'column'   => $this->nameGenerator->generateMultiEnumSnapshotColumnName('field1'),
                                'type'     => 'string',
                                'nullable' => true,
                                'length'   => ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH,
                            ],
                            ExtendHelper::getMultiEnumSnapshotFieldName('field2') => [
                                'column' => 'field2'
                            ]
                        ]
                    ]
                ],
                'property' => [
                    ExtendHelper::getMultiEnumSnapshotFieldName('field1') => []
                ]
            ],
            $entityConfig1->get('schema')
        );
    }

    public function testPostUpdateForMultiEnumFieldsInCustomEntity()
    {
        $entityConfig1 = new Config(new EntityConfigId('extend', 'Extend\EnumValue1'));
        $entityConfig1->set('owner', ExtendScope::OWNER_CUSTOM);
        $entityConfig1->set('is_extend', true);
        $entityConfig1->set(
            'schema',
            [
                'doctrine' => [
                    'Extend\EnumValue1' => [
                        'fields' => [
                            ExtendHelper::getMultiEnumSnapshotFieldName('field2') => [
                                'column' => 'field2'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $entityConfig2 = new Config(new EntityConfigId('extend', 'Extend\EnumValue2'));

        $fieldConfig1 = new Config(new FieldConfigId('extend', 'Extend\EnumValue1', 'field1', 'multiEnum'));
        $fieldConfig2 = new Config(new FieldConfigId('extend', 'Extend\EnumValue1', 'field2', 'multiEnum'));
        $fieldConfig3 = new Config(new FieldConfigId('extend', 'Extend\EnumValue1', 'field3', 'enum'));

        $entityConfigs = [$entityConfig1, $entityConfig2];
        $fieldConfigs  = [$fieldConfig1, $fieldConfig2, $fieldConfig3];

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->at(0))
            ->method('getConfigs')
            ->with(null, true)
            ->will($this->returnValue($entityConfigs));
        $extendConfigProvider->expects($this->at(1))
            ->method('getConfigs')
            ->with($entityConfig1->getId()->getClassName())
            ->will($this->returnValue($fieldConfigs));

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entityConfig1));

        $this->extension->postUpdate();

        $this->assertEquals(
            [
                'doctrine' => [
                    'Extend\EnumValue1' => [
                        'fields' => [
                            ExtendHelper::getMultiEnumSnapshotFieldName('field1') => [
                                'column'   => $this->nameGenerator->generateMultiEnumSnapshotColumnName('field1'),
                                'type'     => 'string',
                                'nullable' => true,
                                'length'   => ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH,
                            ],
                            ExtendHelper::getMultiEnumSnapshotFieldName('field2') => [
                                'column' => 'field2'
                            ]
                        ]
                    ]
                ],
                'property' => [
                    ExtendHelper::getMultiEnumSnapshotFieldName('field1') => []
                ]
            ],
            $entityConfig1->get('schema')
        );
    }

    public function testPostUpdateForDeletedMultiEnumField()
    {
        $entityConfig = new Config(new EntityConfigId('extend', 'Extend\EnumValue1'));
        $entityConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $entityConfig->set('is_extend', true);
        $entityConfig->set(
            'schema',
            [
                'doctrine' => [
                    'Extend\EnumValue1' => [
                        'fields' => [
                            ExtendHelper::getMultiEnumSnapshotFieldName('field2') => [
                                'column' => 'field2'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $fieldConfig = new Config(new FieldConfigId('extend', 'Extend\EnumValue1', 'field1', 'multiEnum'));
        $fieldConfig->set('is_deleted', true);

        $entityConfigs = [$entityConfig];
        $fieldConfigs  = [$fieldConfig];

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->at(0))
            ->method('getConfigs')
            ->with(null, true)
            ->will($this->returnValue($entityConfigs));
        $extendConfigProvider->expects($this->at(1))
            ->method('getConfigs')
            ->with($entityConfig->getId()->getClassName())
            ->will($this->returnValue($fieldConfigs));

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entityConfig));

        $this->extension->postUpdate();

        $this->assertEquals(
            [
                'doctrine' => [
                    'Extend\EnumValue1' => [
                        'fields' => [
                            ExtendHelper::getMultiEnumSnapshotFieldName('field1') => [
                                'column'   => $this->nameGenerator->generateMultiEnumSnapshotColumnName('field1'),
                                'type'     => 'string',
                                'nullable' => true,
                                'length'   => ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH,
                            ],
                            ExtendHelper::getMultiEnumSnapshotFieldName('field2') => [
                                'column' => 'field2'
                            ]
                        ]
                    ]
                ],
                'property' => [
                    ExtendHelper::getMultiEnumSnapshotFieldName('field1') => [
                        'private' => true
                    ]
                ]
            ],
            $entityConfig->get('schema')
        );
    }
}
