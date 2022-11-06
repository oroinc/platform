<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity2;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity3;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;

class EntityConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface */
    private $groupProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigInterface */
    private $config;

    /** @var EntityConfigHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->groupProvider = $this->createMock(AclGroupProviderInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);

        $this->helper = new EntityConfigHelper(
            $this->configProvider,
            $this->groupProvider
        );
    }

    /**
     * @dataProvider getRoutesProvider
     */
    public function testGetRoutes(array $inputData, array $expectedData)
    {
        $configManager = $this->createMock(ConfigManager::class);

        $this->configProvider->expects($this->once())
            ->method('getConfigManager')
            ->willReturn($configManager);

        $configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($inputData['className'] ?? $inputData['class'])
            ->willReturn($inputData['metadata']);

        $this->groupProvider->expects($inputData['group'] ? $this->never() : $this->any())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        $this->assertEquals(
            $expectedData,
            $this->helper->getRoutes($inputData['class'], $inputData['routes'], $inputData['group'])
        );
    }

    /**
     * @dataProvider getConfigValueProvider
     */
    public function testGetConfigValue(array $inputData, mixed $expectedData)
    {
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($inputData['className'] ?? $inputData['class'])
            ->willReturn($this->config);

        $this->config->expects($this->once())
            ->method('get')
            ->with($inputData['name'])
            ->willReturn($inputData['result']);

        $this->assertSame($expectedData, $this->helper->getConfigValue($inputData['class'], $inputData['name']));
    }

    /**
     * @dataProvider strictParamProvider
     */
    public function testGetConfigValueStrictParam(bool $strict)
    {
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('stdClass')
            ->willThrowException(new \RuntimeException('test exception'));

        if ($strict) {
            $this->expectException(\RuntimeException::class);
        }

        $this->assertNull($this->helper->getConfigValue('stdClass', 'param', $strict));
    }

    public function getRoutesProvider(): array
    {
        $config = [
            'route1' => 'route_name1',
            'route2' => 'route_name2',
            'group1Route1' => 'route_group1_name1',
            'group1Route2' => 'route_group1_name2'
        ];

        return [
            'custom routes with default group' => [
                'input' => [
                    'class' => TestEntity2::class,
                    'routes' => ['route1', 'route2', 'unknown'],
                    'group' => null,
                    'metadata' => $this->getEntityMetadata(TestEntity2::class, $config)
                ],
                'expected' => [
                    'route1' => 'route_name1',
                    'route2' => 'route_name2',
                    'unknown' => null
                ]
            ],
            'custom routes with group "group1"' => [
                'input' => [
                    'class' => TestEntity3::class,
                    'routes' => ['route1', 'route2', 'unknown'],
                    'group' => 'group1',
                    'metadata' => $this->getEntityMetadata(TestEntity3::class, $config)
                ],
                'expected' => [
                    'route1' => 'route_group1_name1',
                    'route2' => 'route_group1_name2',
                    'unknown' => null
                ]
            ],
            'by entity object' => [
                'input' => [
                    'class' => new TestEntity2(),
                    'className' => TestEntity2::class,
                    'routes' => ['route1'],
                    'group' => null,
                    'metadata' => $this->getEntityMetadata(TestEntity2::class, $config)
                ],
                'expected' => [
                    'route1' => 'route_name1'
                ]
            ]
        ];
    }

    public function getConfigValueProvider(): array
    {
        return [
            'null result' => [
                'input' => [
                    'class' => TestEntity2::class,
                    'name' => 'name1',
                    'result' => null
                ],
                'expected' => null
            ],
            'value1' => [
                'input' => [
                    'class' => TestEntity2::class,
                    'name' => 'name2',
                    'result' => 'value1'
                ],
                'expected' => 'value1'
            ],
            'by entity object' => [
                'input' => [
                    'class' => new TestEntity2(),
                    'className' => TestEntity2::class,
                    'name' => 'name2',
                    'result' => 'value1'
                ],
                'expected' => 'value1'
            ]
        ];
    }

    public function strictParamProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    private function getEntityMetadata(string $class, array $routes): EntityMetadata
    {
        $meta = new EntityMetadata($class);
        $meta->routes = $routes;

        return $meta;
    }
}
