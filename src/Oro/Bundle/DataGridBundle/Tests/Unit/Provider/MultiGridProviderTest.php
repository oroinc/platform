<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Provider\MultiGridProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MultiGridProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MultiGridProvider */
    protected $multiGridProvider;

    /** @var string */
    protected $entityClass = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var string */
    protected $expectedGridName = 'mygrig1';

    /** @var array */
    protected $permissions = [];

    /** @var array */
    protected $entityConfigs = [
        'Oro\Bundle\UserBundle\Entity\User' => [
            'label' => 'label1',
        ],
        'Oro\Bundle\UserBundle\Entity\Contact' => [
            'label' => 'label2',
        ],
    ];

    public function setUp()
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($attributes, $object) {
                $result = isset($this->permissions[$attributes][$object])
                    ? $this->permissions[$attributes][$object]
                    : true;

                return $result;
            });

        $gridConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'has', 'get'])
            ->getMock();
        $gridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($gridConfigProvider));
        $gridConfigProvider->expects($this->any())
            ->method('has')
            ->with('context')
            ->willReturn(true);
        $gridConfigProvider->expects($this->any())
            ->method('get')
            ->with('context')
            ->will($this->returnValue($this->expectedGridName));

        $configId = $this->createMock(ConfigIdInterface::class);

        $entityConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnCallback(function ($className) use ($configId) {
                return new Config($configId, $this->entityConfigs[$className]);
            }));

        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValueMap([
                ['grid', $gridConfigProvider],
                ['entity', $entityConfigProvider],
            ]));

        $gridManager = $this->createMock(ManagerInterface::class);

        $this->multiGridProvider = new MultiGridProvider(
            $authorizationChecker,
            $configManager,
            $gridManager
        );
    }

    public function testGetContextGridByEntity()
    {
        $gridName = $this->multiGridProvider->getContextGridByEntity($this->entityClass);
        $this->assertEquals($this->expectedGridName, $gridName);
    }

    /**
     * @param array     $permissions
     * @param array     $classNames
     * @param array     $expectedArray
     * @param integer   $expectedCount
     *
     * @dataProvider getEntitiesDataProvider
     */
    public function testGetEntitiesData($permissions, $classNames, $expectedArray, $expectedCount)
    {
        $this->permissions = $permissions;

        $entitiesData = $this->multiGridProvider->getEntitiesData($classNames);
        $this->assertCount($expectedCount, $entitiesData);
        $this->assertEquals($expectedArray, $entitiesData);
    }

    /**
     * @return array
     */
    public function getEntitiesDataProvider()
    {
        return [
            [
                'permissions' => [
                    'VIEW' => [
                        'entity:Oro\Bundle\UserBundle\Entity\User' => true,
                        'entity:Oro\Bundle\UserBundle\Entity\Contact' => true,
                    ],
                ],
                'classNames' => [
                    'Oro\Bundle\UserBundle\Entity\User',
                    'Oro\Bundle\UserBundle\Entity\Contact',
                ],
                'expectedArray' => [
                    [
                        'label' => 'label2',
                        'className' => 'Oro\Bundle\UserBundle\Entity\Contact',
                        'gridName' => 'mygrig1',
                    ],
                    [
                        'label' => 'label1',
                        'className' => 'Oro\Bundle\UserBundle\Entity\User',
                        'gridName' => 'mygrig1',
                    ],
                ],
                'expectedCount' => 2
            ],
            [
                'permissions' => [
                    'VIEW' => [
                        'entity:Oro\Bundle\UserBundle\Entity\User' => false,
                        'entity:Oro\Bundle\UserBundle\Entity\Contact' => true,
                    ],
                ],
                'classNames' => [
                    'Oro\Bundle\UserBundle\Entity\Contact',
                ],
                'expectedArray' => [
                    [
                        'label' => 'label2',
                        'className' => 'Oro\Bundle\UserBundle\Entity\Contact',
                        'gridName' => 'mygrig1',
                    ]
                ],
                'expectedCount' => 1
            ],
            [
                'permissions' => [
                    'VIEW' => [
                        'entity:Oro\Bundle\UserBundle\Entity\User' => true,
                        'entity:Oro\Bundle\UserBundle\Entity\Contact' => false,
                    ],
                ],
                'classNames' => [
                    'Oro\Bundle\UserBundle\Entity\User',
                ],
                'expectedArray' => [
                    [
                        'label' => 'label1',
                        'className' => 'Oro\Bundle\UserBundle\Entity\User',
                        'gridName' => 'mygrig1',
                    ]
                ],
                'expectedCount' => 1
            ],
            [
                'permissions' => [
                    'VIEW' => [
                        'entity:Oro\Bundle\UserBundle\Entity\User' => false,
                        'entity:Oro\Bundle\UserBundle\Entity\Contact' => false,
                    ],
                ],
                'classNames' => [],
                'expectedArray' => [],
                'expectedCount' => 0
            ],
        ];
    }
}
