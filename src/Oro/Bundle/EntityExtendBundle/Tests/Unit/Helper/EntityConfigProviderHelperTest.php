<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
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

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new EntityConfigProviderHelper($this->configManager);

        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('getProviders')
            ->willReturn([$this->configProvider]);
    }


    /**
     * @dataProvider getLayoutParamsDataProvider
     * @param string|null $displayOnly
     * @param array $actions
     * @param array $expected
     */
    public function testGetLayoutParams($displayOnly, array $actions, array $expected)
    {
        $propertyConfig = $this->getMockBuilder(PropertyConfigContainer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig);

        $propertyConfig->expects($this->once())
            ->method('getLayoutActions')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn($actions);

        $propertyConfig->expects($this->once())
            ->method('getRequireJsModules')
            ->willReturn([]);

        $entity = new EntityConfigModel();
        $entity->setMode('some mode');
        $entity->setClassName('SomeClass');

        $configId = new EntityConfigId('extend', 'SomeClass');
        $config = new Config($configId, [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'params' => ['value4', 'value5']
        ]);

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->with('SomeClass')
            ->willReturn($config);

        list($result, $requireJs) = $this->helper->getLayoutParams($entity, $displayOnly);

        $this->assertEquals($expected, $result);
        $this->assertEquals([], $requireJs);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getLayoutParamsDataProvider()
    {
        return [
            'no params' => [
                'displayOnly' => null,
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'args' => ['id' => null]
                    ]
                ]
            ],
            'display only defined only in method' => [
                'displayOnly' => 'somestring',
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true
                    ]
                ],
                'expected' => [

                ]
            ],
            'display only defined in method and action' => [
                'displayOnly' => 'somestring',
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'display_only' => 'somestring'
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'display_only' => 'somestring',
                        'args' => ['id' => null]
                    ]
                ]
            ],
            'display only defined but not equls' => [
                'displayOnly' => 'somestring',
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'display_only' => 'somestring2'
                    ]
                ],
                'expected' => [

                ]
            ],
            'display only not passed but isset in action' => [
                'displayOnly' => null,
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'display_only' => 'somestring2'
                    ]
                ],
                'expected' => [

                ]
            ],
            'filter mode true' => [
                'displayOnly' => null,
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'filter' => ['mode' => 'some mode']
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'filter' => ['mode' => 'some mode'],
                        'args' => ['id' => null]
                    ]
                ]
            ],
            'filter mode false' => [
                'displayOnly' => null,
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'filter' => ['mode' => 'some mode2']
                    ]
                ],
                'expected' => [

                ]
            ],
            'filter via config failed' => [
                'displayOnly' => null,
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'filter' => ['param1' => 'value2'],
                    ]
                ],
                'expected' => [

                ]
            ],
            'filter via array value in config failed' => [
                'displayOnly' => null,
                'actions' => [
                    [
                        'name' => 'some action name',
                        'entity_id' => true,
                        'filter' => ['params' => ['not existing']],
                    ]
                ],
                'expected' => [

                ]
            ],
            'filter true' => [
                'displayOnly' => null,
                'actions' => [
                    [
                        'name' => 'some action name',
                        'filter' => ['param1' => 'value1'],
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'some action name',
                        'filter' => ['param1' => 'value1'],
                    ]
                ]
            ],
        ];
    }
}
