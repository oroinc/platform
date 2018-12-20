<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;

class EntityConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigInterface */
    protected $config;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface */
    protected $groupProvider;

    /** @var EntityConfigHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $this->groupProvider = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface');

        $this->helper = new EntityConfigHelper($this->configProvider, $this->groupProvider);
    }

    public function testGetAvailableRoutes()
    {
        $this->configProvider->expects($this->once())
            ->method('getClassName')
            ->with('TestClass')
            ->willReturn('TestClass');

        $this->configProvider->expects($this->once())
            ->method('getConfigManager')
            ->willReturn($this->configManager);

        $metadata = $this->createMock(EntityMetadata::class);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with('TestClass')
            ->willReturn($metadata);

        $metadata->expects($this->once())
            ->method('getRoutes')
            ->willReturn(['route1' => 'value1']);

        $this->assertEquals(
            [
                'route1' => 'value1',
            ],
            $this->helper->getAvailableRoutes('TestClass')
        );
    }

    public function testGetAvailableRoutesWithoutMetadata()
    {
        $this->configProvider->expects($this->once())->method('getClassName')->willReturnArgument(0);
        $this->configProvider->expects($this->once())->method('getConfigManager')->willReturn($this->configManager);

        $this->configManager->expects($this->once())->method('getEntityMetadata')->with('TestClass')->willReturn(null);

        $this->assertEquals([], $this->helper->getAvailableRoutes('TestClass'));
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getRoutesProvider
     */
    public function testGetRoutes(array $inputData, array $expectedData)
    {
        $this->configProvider->expects($this->once())
            ->method('getClassName')
            ->with($inputData['class'])
            ->willReturn($inputData['class']);

        $this->configProvider->expects($this->once())
            ->method('getConfigManager')
            ->willReturn($this->configManager);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($inputData['class'])
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
     * @param array $inputData
     * @param mixed $expectedData
     *
     * @dataProvider getConfigValueProvider
     */
    public function testGetConfigValue(array $inputData, $expectedData)
    {
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($inputData['class'])
            ->willReturn($this->config);

        $this->config->expects($this->once())
            ->method('get')
            ->with($inputData['name'])
            ->willReturn($inputData['result']);

        $this->assertSame($expectedData, $this->helper->getConfigValue($inputData['class'], $inputData['name']));
    }

    /**
     * @param bool $strict
     *
     * @dataProvider strictParamProvider
     */
    public function testGetConfigValueStrictParam($strict)
    {
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('stdClass')
            ->willThrowException(new \RuntimeException('test exception'));

        if ($strict) {
            $this->expectException('RuntimeException', 'test exception');
        }

        $this->assertNull($this->helper->getConfigValue('stdClass', 'param', $strict));
    }

    /**
     * @return array
     */
    public function getRoutesProvider()
    {
        $config = [
            'route1' => 'route_name1',
            'route2' => 'route_name2',
            'group1Route1' => 'route_group1_name1',
            'group1Route2' => 'route_group1_name2',
        ];

        return [
            'custom routes with default group' => [
                'input' => [
                    'class' => 'Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity2',
                    'routes' => ['route1', 'route2', 'unknown'],
                    'group' => null,
                    'metadata' => $this->getEntityMetadata(
                        'Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity2',
                        $config
                    ),
                ],
                'expected' => [
                    'route1' => 'route_name1',
                    'route2' => 'route_name2',
                    'unknown' => null,
                ],
            ],
            'custom routes with group "group1"' => [
                'input' => [
                    'class' => 'Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity3',
                    'routes' => ['route1', 'route2', 'unknown'],
                    'group' => 'group1',
                    'metadata' => $this->getEntityMetadata(
                        'Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity3',
                        $config
                    ),
                ],
                'expected' => [
                    'route1' => 'route_group1_name1',
                    'route2' => 'route_group1_name2',
                    'unknown' => null,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getConfigValueProvider()
    {
        return [
            'null result' => [
                'input' => [
                    'class' => 'TestEntity',
                    'name' => 'name1',
                    'result' => null,
                ],
                'expected' => null,
            ],
            'value1' => [
                'input' => [
                    'class' => 'TestEntity',
                    'name' => 'name2',
                    'result' => 'value1',
                ],
                'expected' => 'value1',
            ],
        ];
    }

    /**
     * @return array
     */
    public function strictParamProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @param string $class
     * @param array $routes
     * @return EntityMetadata
     */
    protected function getEntityMetadata($class, array $routes)
    {
        $meta = new EntityMetadata($class);

        $meta->routes = $routes;

        return $meta;
    }
}
