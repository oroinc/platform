<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;

class ExtendConfigProcessorTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Test\ExtendConfigProcessorTestBundle\Entity\SomeClass';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ExtendConfigProcessor */
    protected $generator;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->generator     = new ExtendConfigProcessor($this->configManager);
    }

    public function testGeneratorWithEmptyConfigs()
    {
        $this->configManager->expects($this->never())
            ->method('flush');

        $this->generator->processConfigs([]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage A new model can be created for custom entity only. Class:
     */
    public function testModificationOfNonConfigurableEntity()
    {
        $configs = [
            self::CLASS_NAME => [
                'configs' => ['entity' => ['icon' => 'icon1']]
            ]
        ];

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, false],
                    ]
                )
            );

        $this->generator->processConfigs($configs);
    }

    public function testModificationOfNonConfigurableEntityWithOnlyFieldsTypeSpecified()
    {
        $configs = [
            self::CLASS_NAME => [
                'fields' => [
                    'field1' => [
                        'type' => 'integer'
                    ]
                ]
            ]
        ];

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, false],
                    ]
                )
            );

        $this->configManager->expects($this->never())
            ->method('flush');

        $this->generator->processConfigs($configs);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage A new model can be created for custom entity only. Class:
     */
    public function testModificationOfNonConfigurableEntityWithFieldsTypeSpecifiedAndHasEntityConfigs()
    {
        $configs = [
            self::CLASS_NAME => [
                'configs' => [],
                'fields' => [
                    'field1' => [
                        'type' => 'integer'
                    ]
                ]
            ]
        ];

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, false],
                    ]
                )
            );

        $this->generator->processConfigs($configs);
    }

    public function testModificationOfNonExtendEntity()
    {
        $configs = [
            self::CLASS_NAME => [
                'configs' => [
                    'entity' => [
                        'icon' => 'icon1'
                    ]
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);
        $entityConfigEntity = $this->createConfig('entity', self::CLASS_NAME);

        // config providers configuration
        $extendConfigProvider = $this->getConfigProviderMock();
        $entityConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['entity', $entityConfigProvider],
                    ]
                )
            );
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, true],
                    ]
                )
            );
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, $extendConfigEntity],
                    ]
                )
            );
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, $entityConfigEntity],
                    ]
                )
            );

        $this->configManager->expects($this->once())
            ->method('flush');

        $this->generator->processConfigs($configs);

        $this->assertEquals(
            ['state' => ExtendScope::STATE_UPDATED],
            $extendConfigEntity->all()
        );
        $this->assertEquals(
            ['icon' => 'icon1'],
            $entityConfigEntity->all()
        );
    }

    public function testCreateCustomEntity()
    {
        $testClassName = ExtendConfigDumper::ENTITY . 'TestEntity';
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
        $extendConfigProvider = $this->getConfigProviderMock();
        $entityConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['entity', $entityConfigProvider],
                    ]
                )
            );
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$testClassName, null, false],
                    ]
                )
            );
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$testClassName, null, $extendConfigEntity],
                    ]
                )
            );
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [$testClassName, null, $entityConfigEntity],
                    ]
                )
            );

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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage An extend field "field1" cannot be added to non extend entity
     */
    public function testAddExtendFieldToNonExtendEntity()
    {
        $testFieldName = 'field1';
        $configs       = [
            self::CLASS_NAME => [
                'fields' => [
                    $testFieldName => [
                        'type'    => 'string',
                        'configs' => [
                            'extend' => [
                                'extend' => true
                            ],
                        ]
                    ],
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);

        // config providers configuration
        $extendConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                    ]
                )
            );
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, true],
                    ]
                )
            );
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, $extendConfigEntity],
                    ]
                )
            );

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
                                'is_visible' => false
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);
        $extendConfigEntity->set('extend', true);
        $extendConfigEntity->set('is_extend', true);
        $extendConfigField   = $this->createConfig('extend', self::CLASS_NAME, $testFieldName, 'string');
        $datagridConfigField = $this->createConfig('datagrid', self::CLASS_NAME, $testFieldName, 'string');

        // config providers configuration
        $extendConfigProvider   = $this->getConfigProviderMock();
        $datagridConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['datagrid', $datagridConfigProvider],
                    ]
                )
            );
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, true],
                        [self::CLASS_NAME, $testFieldName, false],
                    ]
                )
            );
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, $extendConfigEntity],
                        [self::CLASS_NAME, $testFieldName, $extendConfigField],
                    ]
                )
            );
        $datagridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, $testFieldName, $datagridConfigField],
                    ]
                )
            );

        $this->configManager->expects($this->any())
            ->method('createConfigFieldModel')
            ->with(self::CLASS_NAME, $testFieldName, 'string', ConfigModelManager::MODE_DEFAULT);
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->generator->processConfigs($configs);

        $this->assertEquals(
            ['extend' => true, 'is_extend' => true],
            $extendConfigEntity->all()
        );
        $this->assertEquals(
            ['state' => ExtendScope::STATE_NEW, 'owner' => ExtendScope::OWNER_CUSTOM],
            $extendConfigField->all()
        );
        $this->assertEquals(
            ['is_visible' => false],
            $datagridConfigField->all()
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
                                'is_visible' => false
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $extendConfigEntity = $this->createConfig('extend', self::CLASS_NAME);
        $extendConfigEntity->set('extend', true);
        $extendConfigEntity->set('is_extend', true);
        $extendConfigField   = $this->createConfig('extend', self::CLASS_NAME, $testFieldName, 'string');
        $datagridConfigField = $this->createConfig('datagrid', self::CLASS_NAME, $testFieldName, 'string');
        $configFieldModel    = new FieldConfigModel($testFieldName, 'string');

        // config providers configuration
        $extendConfigProvider   = $this->getConfigProviderMock();
        $datagridConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['datagrid', $datagridConfigProvider],
                    ]
                )
            );
        // hasConfig/getConfig expectations
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, true],
                        [self::CLASS_NAME, $testFieldName, true],
                    ]
                )
            );
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, null, $extendConfigEntity],
                        [self::CLASS_NAME, $testFieldName, $extendConfigField],
                    ]
                )
            );
        $datagridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::CLASS_NAME, $testFieldName, $datagridConfigField],
                    ]
                )
            );

        $this->configManager->expects($this->never())
            ->method('createConfigFieldModel');
        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::CLASS_NAME, $testFieldName)
            ->will($this->returnValue($configFieldModel));
        $this->configManager->expects($this->once())
            ->method('changeFieldType')
            ->with(self::CLASS_NAME, $testFieldName, 'integer');
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->generator->processConfigs($configs);

        $this->assertEquals(
            ['extend' => true, 'is_extend' => true],
            $extendConfigEntity->all()
        );
        $this->assertEquals(
            ['state' => ExtendScope::STATE_UPDATED, 'owner' => ExtendScope::OWNER_CUSTOM],
            $extendConfigField->all()
        );
        $this->assertEquals(
            ['is_visible' => false],
            $datagridConfigField->all()
        );
    }

    /**
     * @param string      $scope
     * @param string      $className
     * @param string|null $fieldName
     * @param string|null $fieldType
     * @return Config
     */
    protected function createConfig($scope, $className, $fieldName = null, $fieldType = null)
    {
        $configId = $fieldName
            ? new FieldConfigId($scope, $className, $fieldName, $fieldType)
            : new EntityConfigId($scope, $className);

        return new Config($configId);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
