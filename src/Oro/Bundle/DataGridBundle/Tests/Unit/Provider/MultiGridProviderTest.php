<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Provider\MultiGridProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MultiGridProviderTest extends \PHPUnit\Framework\TestCase
{
    private const GRID_NAME = 'mygrig1';

    private array $permissions = [];
    private array $entityConfigs = [
        User::class => [
            'label' => 'label1',
        ],
        'Oro\Bundle\UserBundle\Entity\Contact' => [
            'label' => 'label2',
        ],
    ];

    /** @var MultiGridProvider */
    private $multiGridProvider;

    protected function setUp(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($attributes, $object) {
                return $this->permissions[$attributes][$object] ?? true;
            });

        $gridConfig = $this->createMock(ConfigInterface::class);
        $gridConfigProvider = $this->createMock(ConfigProvider::class);
        $gridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($gridConfig);
        $gridConfig->expects($this->any())
            ->method('has')
            ->with('context')
            ->willReturn(true);
        $gridConfig->expects($this->any())
            ->method('get')
            ->with('context')
            ->willReturn(self::GRID_NAME);

        $configId = $this->createMock(ConfigIdInterface::class);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(function ($className) use ($configId) {
                return new Config($configId, $this->entityConfigs[$className]);
            });

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['grid', $gridConfigProvider],
                ['entity', $entityConfigProvider],
            ]);

        $gridManager = $this->createMock(ManagerInterface::class);

        $this->multiGridProvider = new MultiGridProvider(
            $authorizationChecker,
            $configManager,
            $gridManager
        );
    }

    public function testGetContextGridByEntity()
    {
        $gridName = $this->multiGridProvider->getContextGridByEntity(User::class);
        $this->assertEquals(self::GRID_NAME, $gridName);
    }

    /**
     * @dataProvider getEntitiesDataProvider
     */
    public function testGetEntitiesData(
        array $permissions,
        array $classNames,
        array $expectedArray,
        int $expectedCount
    ) {
        $this->permissions = $permissions;

        $entitiesData = $this->multiGridProvider->getEntitiesData($classNames);
        $this->assertCount($expectedCount, $entitiesData);
        $this->assertEquals($expectedArray, $entitiesData);
    }

    public function getEntitiesDataProvider(): array
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
                    User::class,
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
                        'className' => User::class,
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
                    User::class,
                ],
                'expectedArray' => [
                    [
                        'label' => 'label1',
                        'className' => User::class,
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
