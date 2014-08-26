<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
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
        $fieldConfig2->set('state', ExtendScope::STATE_NEW);
        $fieldConfig3 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field3', 'enum'));
        $fieldConfig3->set('state', ExtendScope::STATE_UPDATED);
        $fieldConfig4 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field4', 'manyToOne'));
        $fieldConfig4->set('state', ExtendScope::STATE_NEW);

        $enumFieldConfig1 = new Config(new FieldConfigId('enum', 'Test\EnumValue1', 'field1', 'enum'));
        $enumFieldConfig1->set('enum_name', 'Test Enum 1');
        $enumFieldConfig1->set('enum_public', false);
        $enumFieldConfig2 = new Config(new FieldConfigId('enum', 'Test\EnumValue1', 'field2', 'enum'));
        $enumFieldConfig2->set('enum_name', 'Test Enum 2');
        $enumFieldConfig2->set('enum_public', true);

        $entityConfigs = [$entityConfig1, $entityConfig2];
        $fieldConfigs  = [$fieldConfig1, $fieldConfig2, $fieldConfig3, $fieldConfig4];

        $enumCode1           = ExtendHelper::buildEnumCode('Test Enum 1');
        $enumCode2           = ExtendHelper::buildEnumCode('Test Enum 2');
        $enumValueClassName1 = ExtendHelper::buildEnumValueClassName($enumCode1);
        $enumValueClassName2 = ExtendHelper::buildEnumValueClassName($enumCode2);

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

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfigEntityModel')
            ->will(
                $this->returnValueMap(
                    [
                        [$enumValueClassName1, false],
                        [$enumValueClassName2, true],
                    ]
                )
            );

        $this->configManager->expects($this->at(3))
            ->method('createConfigEntityModel')
            ->with($enumValueClassName1, ConfigModelManager::MODE_HIDDEN);
        $this->configManager->expects($this->at(4))
            ->method('createConfigFieldModel')
            ->with($enumValueClassName1, 'id', 'string');
        $this->configManager->expects($this->at(5))
            ->method('createConfigFieldModel')
            ->with($enumValueClassName1, 'name', 'string');
        $this->configManager->expects($this->at(6))
            ->method('createConfigFieldModel')
            ->with($enumValueClassName1, 'priority', 'integer');
        $this->configManager->expects($this->at(7))
            ->method('createConfigFieldModel')
            ->with($enumValueClassName1, 'default', 'boolean');

        $this->relationBuilder->expects($this->at(0))
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
                        'inherit'   => 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue'
                    ],
                    'grouping'   => [
                        'groups' => ['enum', 'dictionary']
                    ],
                    'enum'       => [
                        'code'     => $enumCode1,
                        'public'   => false,
                        'multiple' => false
                    ],
                    'dictionary' => [
                        'virtual_fields' => ['id', 'name']
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at(1))
            ->method('updateFieldConfigs')
            ->with(
                $enumValueClassName1,
                'id',
                [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode1, 'id'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode1, 'id')
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at(2))
            ->method('updateFieldConfigs')
            ->with(
                $enumValueClassName1,
                'name',
                [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode1, 'name'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode1, 'name')
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at(3))
            ->method('updateFieldConfigs')
            ->with(
                $enumValueClassName1,
                'priority',
                [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode1, 'priority'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode1, 'priority')
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at(4))
            ->method('updateFieldConfigs')
            ->with(
                $enumValueClassName1,
                'default',
                [
                    'entity' => [
                        'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode1, 'default'),
                        'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode1, 'default')
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at(5))
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($entityConfig1),
                $enumValueClassName1,
                'field1',
                'name',
                [
                    'enum'         => [
                        'enum_code' => $enumCode1
                    ],
                    'importexport' => [
                        'process_as_scalar' => true
                    ]
                ],
                'enum'
            );
        $this->relationBuilder->expects($this->at(6))
            ->method('updateEntityConfigs')
            ->with(
                $enumValueClassName2,
                [
                    'enum' => [
                        'public' => true
                    ]
                ]
            );
        $this->relationBuilder->expects($this->at(7))
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
                    ],
                    'importexport' => [
                        'process_as_scalar' => true
                    ]
                ],
                'multiEnum'
            );

        $this->extension->preUpdate();
    }

    public function testPostUpdateForEnumValues()
    {
        $entityConfig1 = new Config(new EntityConfigId('extend', 'Test\EnumValue1'));
        $entityConfig1->set('inherit', 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue');
        $entityConfig2 = new Config(new EntityConfigId('extend', 'Test\EnumValue2'));
        $entityConfig2->set('inherit', 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue');
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

    public function testPostUpdateForMultipleEnumFields()
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
                            ExtendHelper::getMultipleEnumSnapshotFieldName('field2') => [
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
                            ExtendHelper::getMultipleEnumSnapshotFieldName('field1') => [
                                'column'   => $this->nameGenerator->generateMultipleEnumSnapshotColumnName('field1'),
                                'type'     => 'string',
                                'nullable' => true,
                                'length'   => 500,
                            ],
                            ExtendHelper::getMultipleEnumSnapshotFieldName('field2') => [
                                'column' => 'field2'
                            ]
                        ]
                    ]
                ],
                'property' => [
                    ExtendHelper::getMultipleEnumSnapshotFieldName('field1') =>
                        ExtendHelper::getMultipleEnumSnapshotFieldName('field1')
                ]
            ],
            $entityConfig1->get('schema')
        );
    }
}
