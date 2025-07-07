<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendConfigProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'Test\ExtendConfigProcessorTestBundle\Entity\SomeClass';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ExtendConfigProcessor */
    private $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->generator = new ExtendConfigProcessor($this->configManager);
    }

    public function testGeneratorWithEmptyConfigs()
    {
        $this->configManager->expects($this->never())
            ->method('flush');

        $this->generator->processConfigs([]);
    }

    public function testModificationOfNonConfigurableEntity()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A new model can be created for custom entity only. Class:');

        $configs = [
            self::CLASS_NAME => [
                'configs' => ['entity' => ['icon' => 'icon1']]
            ]
        ];

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, false]
            ]);

        $this->generator->processConfigs($configs);
    }

    public function testModificationOfNonConfigurableEntityWithOnlyFieldsTypeSpecified()
    {
        $configs = [
            self::CLASS_NAME => [
                'fields' => [
                    'field1' => [
                        'type' => 'integer'
                    ],
                    'field2' => [
                        'type'    => 'string',
                        'configs' => [
                            'extend' => [
                                'length' => 200
                            ]
                        ]
                    ],
                    'field3' => [
                        'type'    => 'text',
                        'configs' => [
                            'extend' => [
                                'length' => null
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, false]
            ]);

        $this->configManager->expects($this->never())
            ->method('flush');

        $this->generator->processConfigs($configs);
    }

    public function testModificationOfNonConfigurableEntityWithFieldsTypeSpecifiedAndHasEntityConfigs()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A new model can be created for custom entity only. Class:');

        $configs = [
            self::CLASS_NAME => [
                'configs' => [],
                'fields'  => [
                    'field1' => [
                        'type' => 'integer'
                    ]
                ]
            ]
        ];

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, false]
            ]);

        $this->generator->processConfigs($configs);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testModificationOfNonExtendEntity()
    {
        $configs = [
            self::CLASS_NAME                      => [
                'configs' => [
                    'entity' => [
                        'icon' => 'icon1'
                    ],
                    'append' => [
                        'attr1' => ['newItem'],
                        'attr2' => 'newItem',
                        'attr3' => ['newItem'],
                    ]
                ]
            ],
            ExtendConfigProcessor::APPEND_CONFIGS => [
                self::CLASS_NAME => [
                    'configs' => [
                        'append' => ['attr1', 'attr2', 'attr3']
                    ]
                ],
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);
        $entityConfigEntity = $this->createConfig('entity', self::CLASS_NAME);
        $appendConfigEntity = $this->createConfig('append', self::CLASS_NAME);
        $appendConfigEntity->set('attr1', ['existingItem']);
        $appendConfigEntity->set('attr2', ['existingItem']);

        // config providers configuration
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $appendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['entity', $entityConfigProvider],
                ['append', $appendConfigProvider],
            ]);
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, true]
            ]);
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, $extendConfigEntity]
            ]);
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, $entityConfigEntity]
            ]);
        $appendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, $appendConfigEntity]
            ]);

        $this->configManager->expects($this->once())
            ->method('flush');

        $this->generator->processConfigs($configs);

        $this->assertEquals([], $extendConfigEntity->all());
        $this->assertEquals(
            ['icon' => 'icon1'],
            $entityConfigEntity->all()
        );
        $this->assertEquals(
            [
                'attr1' => ['existingItem', 'newItem'],
                'attr2' => ['existingItem', 'newItem'],
                'attr3' => ['newItem'],
            ],
            $appendConfigEntity->all()
        );
    }

    public function testCreateCustomEntity()
    {
        $testClassName = ExtendHelper::ENTITY_NAMESPACE . 'TestEntity';
        $configs       = [
            $testClassName => [
                'configs' => [
                    'extend' => [
                        'owner'     => ExtendScope::OWNER_CUSTOM,
                        'is_extend' => true,
                    ],
                    'entity' => [
                        'icon' => 'icon1'
                    ]
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', $testClassName);
        $entityConfigEntity = $this->createConfig('entity', $testClassName);

        // config providers configuration
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['entity', $entityConfigProvider],
            ]);
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [$testClassName, null, false]
            ]);
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [$testClassName, null, $extendConfigEntity]
            ]);
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [$testClassName, null, $entityConfigEntity]
            ]);

        $this->configManager->expects($this->once())
            ->method('flush');

        $this->generator->processConfigs($configs);

        $this->assertEquals(
            [
                'state'     => ExtendScope::STATE_NEW,
                'owner'     => ExtendScope::OWNER_CUSTOM,
                'is_extend' => true
            ],
            $extendConfigEntity->all()
        );
        $this->assertEquals(
            ['icon' => 'icon1'],
            $entityConfigEntity->all()
        );
    }

    public function testAddExtendFieldToNonExtendEntity()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('An extend field "field1" cannot be added to non extend entity');

        $testFieldName = 'field1';
        $configs       = [
            self::CLASS_NAME => [
                'fields' => [
                    $testFieldName => [
                        'type'    => 'string',
                        'configs' => [
                            'extend' => [
                                'is_extend' => true
                            ],
                        ]
                    ],
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);

        // config providers configuration
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
            ]);
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, true]
            ]);
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, $extendConfigEntity]
            ]);

        $this->generator->processConfigs($configs);
    }

    public function testNewField()
    {
        $testFieldName = 'field1';
        $configs       = [
            self::CLASS_NAME => [
                'fields' => [
                    $testFieldName => [
                        'type'    => 'string',
                        'configs' => [
                            'extend'   => [
                                'owner' => ExtendScope::OWNER_CUSTOM
                            ],
                            'datagrid' => [
                                'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);
        $extendConfigEntity->set('is_extend', true);
        $extendConfigField   = $this->createConfig('extend', self::CLASS_NAME, $testFieldName, 'string');
        $datagridConfigField = $this->createConfig('datagrid', self::CLASS_NAME, $testFieldName, 'string');

        // config providers configuration
        $extendConfigProvider   = $this->createMock(ConfigProvider::class);
        $datagridConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['datagrid', $datagridConfigProvider],
            ]);
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, true],
                [self::CLASS_NAME, $testFieldName, false],
            ]);
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, $extendConfigEntity],
                [self::CLASS_NAME, $testFieldName, $extendConfigField],
            ]);
        $datagridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, $testFieldName, $datagridConfigField]
            ]);

        $this->configManager->expects($this->any())
            ->method('createConfigFieldModel')
            ->with(self::CLASS_NAME, $testFieldName, 'string', ConfigModel::MODE_DEFAULT);
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->generator->processConfigs($configs);

        $this->assertEquals(
            ['is_extend' => true],
            $extendConfigEntity->all()
        );
        $this->assertEquals(
            ['state' => ExtendScope::STATE_NEW, 'owner' => ExtendScope::OWNER_CUSTOM],
            $extendConfigField->all()
        );
        $this->assertEquals(
            ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
            $datagridConfigField->all()
        );
    }

    public function testEntityConfigItemMergedOptionsIsUnique(): void
    {
        $testClassName = ExtendHelper::ENTITY_NAMESPACE . 'TestEntity';
        $configs = [
            $testClassName => [
                'configs' => [
                    'extend' => [
                        'activities' => [
                            'call'
                        ]
                    ],
                ],
            ],
        ];
        $configs[ExtendConfigProcessor::APPEND_CONFIGS][$testClassName] = [
            'configs' => [
                'extend' => ['activities']
            ]
        ];
        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);
        $extendConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
            ]);
        $extendConfigProvider
            ->method('getConfig')
            ->willReturnMap([
                [$testClassName, null, $extendConfigEntity],
            ]);

        // process the same config twice to check is values is not duplicated
        $this->generator->processConfigs($configs);
        $this->generator->processConfigs($configs);

        $this->assertEquals(
            ['state' => ExtendScope::STATE_NEW, 'activities' => ['call']],
            $extendConfigEntity->getValues()
        );
    }

    public function testExistingField()
    {
        $testFieldName = 'field1';
        $configs       = [
            self::CLASS_NAME => [
                'fields' => [
                    $testFieldName => [
                        'type'    => 'integer',
                        'configs' => [
                            'extend'   => [
                                'owner' => ExtendScope::OWNER_CUSTOM
                            ],
                            'datagrid' => [
                                'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);
        $extendConfigEntity->set('is_extend', true);
        $extendConfigField   = $this->createConfig('extend', self::CLASS_NAME, $testFieldName, 'string');
        $datagridConfigField = $this->createConfig('datagrid', self::CLASS_NAME, $testFieldName, 'string');
        $configFieldModel    = new FieldConfigModel($testFieldName, 'string');

        // config providers configuration
        $extendConfigProvider   = $this->createMock(ConfigProvider::class);
        $datagridConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['datagrid', $datagridConfigProvider],
            ]);
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, true],
                [self::CLASS_NAME, $testFieldName, true],
            ]);
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, $extendConfigEntity],
                [self::CLASS_NAME, $testFieldName, $extendConfigField],
            ]);
        $datagridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, $testFieldName, $datagridConfigField]
            ]);

        $this->configManager->expects($this->never())
            ->method('createConfigFieldModel');
        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::CLASS_NAME, $testFieldName)
            ->willReturn($configFieldModel);
        $this->configManager->expects($this->once())
            ->method('changeFieldType')
            ->with(self::CLASS_NAME, $testFieldName, 'integer');
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->generator->processConfigs($configs);

        $this->assertEquals(
            ['is_extend' => true],
            $extendConfigEntity->all()
        );
        $this->assertEquals(
            ['state' => ExtendScope::STATE_UPDATE, 'owner' => ExtendScope::OWNER_CUSTOM],
            $extendConfigField->all()
        );
        $this->assertEquals(
            ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
            $datagridConfigField->all()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAppendForExistingField()
    {
        $testFieldName = 'field1';
        $configs       = [
            self::CLASS_NAME                      => [
                'fields' => [
                    $testFieldName => [
                        'type'    => 'integer',
                        'configs' => [
                            'extend' => [
                                'owner'              => ExtendScope::OWNER_CUSTOM,
                                'array_attr1'        => ['val1'],
                                'array_attr2'        => ['val2'],
                                'array_attr3.attr31' => ['val3'],
                                'array_attr4.attr41' => 'val4'
                            ]
                        ]
                    ]
                ]
            ],
            ExtendConfigProcessor::APPEND_CONFIGS => [
                self::CLASS_NAME => [
                    'fields' => [
                        $testFieldName => [
                            'configs' => [
                                'extend' => ['array_attr1', 'array_attr2', 'array_attr3.attr31']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);
        $extendConfigEntity->set('is_extend', true);
        $extendConfigField = $this->createConfig('extend', self::CLASS_NAME, $testFieldName, 'string');
        $configFieldModel  = new FieldConfigModel($testFieldName, 'string');

        // config providers configuration
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, true],
                [self::CLASS_NAME, $testFieldName, true],
            ]);
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [self::CLASS_NAME, null, $extendConfigEntity],
                [self::CLASS_NAME, $testFieldName, $extendConfigField],
            ]);

        $this->configManager->expects($this->never())
            ->method('createConfigFieldModel');
        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::CLASS_NAME, $testFieldName)
            ->willReturn($configFieldModel);
        $this->configManager->expects($this->once())
            ->method('changeFieldType')
            ->with(self::CLASS_NAME, $testFieldName, 'integer');
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->generator->processConfigs($configs);

        $this->assertEquals(
            ['is_extend' => true],
            $extendConfigEntity->all()
        );
        $this->assertEquals(
            [
                'state'       => ExtendScope::STATE_UPDATE,
                'owner'       => ExtendScope::OWNER_CUSTOM,
                'array_attr1' => ['val1'],
                'array_attr2' => ['val2'],
                'array_attr3' => [
                    'attr31' => ['val3']
                ],
                'array_attr4' => [
                    'attr41' => 'val4'
                ]
            ],
            $extendConfigField->all()
        );
    }

    private function createConfig(
        string $scope,
        string $className,
        ?string $fieldName = null,
        ?string $fieldType = null
    ): Config {
        $configId = $fieldName
            ? new FieldConfigId($scope, $className, $fieldName, $fieldType)
            : new EntityConfigId($scope, $className);

        return new Config($configId);
    }
}
