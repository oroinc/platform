<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigProviderHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

class EntityConfigProviderHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityConfigProviderHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->helper = new EntityConfigProviderHelper($this->configManager);
    }

    /**
     * @dataProvider getLayoutParamsForActionsDataProvider
     */
    public function testGetLayoutParamsForActions(?string $displayOnly, array $actions, array $expected)
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects(self::once())
            ->method('getProviders')
            ->willReturn([$configProvider]);

        $propertyConfig = $this->createMock(PropertyConfigContainer::class);
        $configProvider->expects(self::once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig);

        $propertyConfig->expects(self::once())
            ->method('getLayoutActions')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn($actions);
        $propertyConfig->expects(self::once())
            ->method('getJsModules')
            ->willReturn([]);

        $entity = new EntityConfigModel('Test\Entity');
        $entity->setMode(ConfigModel::MODE_DEFAULT);

        $configId = new EntityConfigId('extend', $entity->getClassName());
        $config = new Config($configId, [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'params' => ['value4', 'value5']
        ]);

        $configProvider->expects(self::any())
            ->method('getConfig')
            ->with($entity->getClassName())
            ->willReturn($config);

        [$resultActions, $resultJsModules] = $this->helper->getLayoutParams($entity, $displayOnly);

        self::assertEquals($expected, $resultActions);
        self::assertEquals([], $resultJsModules);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getLayoutParamsForActionsDataProvider(): array
    {
        return [
            'no params'                                   => [
                'displayOnly' => null,
                'actions'     => [
                    [
                        'name'      => 'some action name',
                        'entity_id' => true
                    ]
                ],
                'expected'    => [
                    [
                        'name'      => 'some action name',
                        'entity_id' => true,
                        'args'      => ['id' => null]
                    ]
                ]
            ],
            'display only defined only in method'         => [
                'displayOnly' => 'somestring',
                'actions'     => [
                    [
                        'name'      => 'some action name',
                        'entity_id' => true
                    ]
                ],
                'expected'    => []
            ],
            'display only defined in method and action'   => [
                'displayOnly' => 'somestring',
                'actions'     => [
                    [
                        'name'         => 'some action name',
                        'entity_id'    => true,
                        'display_only' => 'somestring'
                    ]
                ],
                'expected'    => [
                    [
                        'name'         => 'some action name',
                        'entity_id'    => true,
                        'display_only' => 'somestring',
                        'args'         => ['id' => null]
                    ]
                ]
            ],
            'display only defined but not equls'          => [
                'displayOnly' => 'somestring',
                'actions'     => [
                    [
                        'name'         => 'some action name',
                        'entity_id'    => true,
                        'display_only' => 'somestring2'
                    ]
                ],
                'expected'    => []
            ],
            'display only not passed but isset in action' => [
                'displayOnly' => null,
                'actions'     => [
                    [
                        'name'         => 'some action name',
                        'entity_id'    => true,
                        'display_only' => 'somestring2'
                    ]
                ],
                'expected'    => []
            ],
            'filter mode true'                            => [
                'displayOnly' => null,
                'actions'     => [
                    [
                        'name'      => 'some action name',
                        'entity_id' => true,
                        'filter'    => ['mode' => ConfigModel::MODE_DEFAULT]
                    ]
                ],
                'expected'    => [
                    [
                        'name'      => 'some action name',
                        'entity_id' => true,
                        'filter'    => ['mode' => ConfigModel::MODE_DEFAULT],
                        'args'      => ['id' => null]
                    ]
                ]
            ],
            'filter mode false'                           => [
                'displayOnly' => null,
                'actions'     => [
                    [
                        'name'      => 'some action name',
                        'entity_id' => true,
                        'filter'    => ['mode' => ConfigModel::MODE_READONLY]
                    ]
                ],
                'expected'    => []
            ],
            'filter via config failed'                    => [
                'displayOnly' => null,
                'actions'     => [
                    [
                        'name'      => 'some action name',
                        'entity_id' => true,
                        'filter'    => ['param1' => 'value2']
                    ]
                ],
                'expected'    => []
            ],
            'filter via array value in config failed'     => [
                'displayOnly' => null,
                'actions'     => [
                    [
                        'name'      => 'some action name',
                        'entity_id' => true,
                        'filter'    => ['params' => ['not existing']]
                    ]
                ],
                'expected'    => []
            ],
            'filter true'                                 => [
                'displayOnly' => null,
                'actions'     => [
                    [
                        'name'   => 'some action name',
                        'filter' => ['param1' => 'value1']
                    ]
                ],
                'expected'    => [
                    [
                        'name'   => 'some action name',
                        'filter' => ['param1' => 'value1']
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getLayoutParamsForJsModulesDataProvider
     */
    public function testGetLayoutParamsForJsModules(array $jsModules, array $expected)
    {
        $configProvider1 = $this->createMock(ConfigProvider::class);
        $configProvider2 = $this->createMock(ConfigProvider::class);
        $this->configManager->expects(self::once())
            ->method('getProviders')
            ->willReturn([$configProvider1, $configProvider2]);

        $propertyConfig1 = $this->createMock(PropertyConfigContainer::class);
        $configProvider1->expects(self::once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig1);
        $propertyConfig2 = $this->createMock(PropertyConfigContainer::class);
        $configProvider2->expects(self::once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig2);

        $propertyConfig1->expects(self::once())
            ->method('getLayoutActions')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([]);
        $propertyConfig2->expects(self::once())
            ->method('getLayoutActions')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([]);

        $propertyConfig1->expects(self::once())
            ->method('getJsModules')
            ->willReturn($jsModules[0]);
        $propertyConfig2->expects(self::once())
            ->method('getJsModules')
            ->willReturn($jsModules[1]);

        $entity = new EntityConfigModel('Test\Entity');

        [$resultActions, $resultJsModules] = $this->helper->getLayoutParams($entity);

        self::assertEquals($expected, $resultJsModules);
        self::assertEquals([], $resultActions);
    }

    public function getLayoutParamsForJsModulesDataProvider(): array
    {
        return [
            'no JS modules'     => [
                'jsModules' => [[], []],
                'expected'  => []
            ],
            'one JS module'     => [
                'jsModules' => [['module_1'], []],
                'expected'  => ['module_1']
            ],
            'several JS module' => [
                'jsModules' => [['module_1', 'module_2'], ['module_3', 'module_4']],
                'expected'  => ['module_1', 'module_2', 'module_3', 'module_4']
            ]
        ];
    }
}
