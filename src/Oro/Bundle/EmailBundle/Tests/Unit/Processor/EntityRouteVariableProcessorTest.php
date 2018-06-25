<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Processor;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Processor\EntityRouteVariableProcessor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class EntityRouteVariableProcessorTest extends \PHPUnit\Framework\TestCase
{
    const TEST_GENERATED_ROUTE = 'generated_route';
    const TEST_BASE_PATH = 'http://localhost/';

    /** @var EntityRouteVariableProcessor */
    protected $processor;

    /** @var  RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var  DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var  ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var  EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigManager;

    /** @var  ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);

        $this->extendConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConfigManager->expects($this->any())->method('getProvider')->willReturnMap([
            ['extend', $this->extendConfigProvider],
        ]);

        $this->processor = new EntityRouteVariableProcessor(
            $this->router,
            $this->doctrineHelper,
            $this->configManager,
            $this->entityConfigManager
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->processor, $this->router, $this->doctrineHelper, $this->configManager, $this->entityConfigManager);
    }

    /**
     * @param string $expected
     * @param string $variable
     * @param array $definition
     * @param array $data
     *
     * @dataProvider processDataProvider
     */
    public function testProcess($expected, $variable, array $definition, array $data = [])
    {
        if (isset($definition['route'])) {
            $routeCollection = $this->createMock(RouteCollection::class);
            $routeCollection->expects($this->once())
                ->method('get')
                ->with($definition['route'])
                ->willReturnArgument(0);
    
            $this->router->expects($this->once())->method('getRouteCollection')->willReturn($routeCollection);
            $this->router->expects($this->once())->method('generate')->willReturn(self::TEST_GENERATED_ROUTE);
    
            if (!preg_match('/^.*_index$/', $definition['route'])) {
                $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->willReturn(1);
            } else {
                $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifier');
            }
    
            $this->configManager->expects($this->once())->method('get')->with('oro_ui.application_url')
                ->willReturn(self::TEST_BASE_PATH);
        }
    
        if (isset($data['entity'])) {
            $this->doctrineHelper->expects($this->once())->method('isManageableEntity')->willReturnArgument(0);
    
            $config = $this->createMock(ConfigInterface::class);
            $config->expects($this->once())->method('is')->with('is_extend')->willReturn(false);
            $this->extendConfigProvider->expects($this->once())->method('hasConfig')->willReturn(true);
            $this->extendConfigProvider->expects($this->once())->method('getConfig')->willReturn($config);
        }
    
        $this->assertEquals($expected, $this->processor->process($variable, $definition, $data));
    }
    
    /**
     * @return \Generator
     */
    public function processDataProvider()
    {
        yield 'non-supported variable' => [
            'expected' => '{{ \'test\' }}',
            'variable' => 'test',
            'definition' => [],
            'data' => [],
        ];
    
        yield 'empty definition' => [
            'expected' => '{{ \'entity.url.index\' }}',
            'variable' => 'entity.url.index',
            'definition' => [],
            'data' => [],
        ];
    
        yield 'empty data' => [
            'expected' => '{{ \'entity.url.index\' }}',
            'variable' => 'entity.url.index',
            'definition' => [],
            'data' => [],
        ];
    
        yield 'test entity index' => [
            'expected' => sprintf('{{ \'%s\' }}', self::TEST_BASE_PATH . self::TEST_GENERATED_ROUTE),
            'variable' => 'entity.url.index',
            'definition' => [
                'route' => 'route_index',
            ],
            'data' => [
                'entity' => new \stdClass(),
            ],
        ];
    
        yield 'test entity view' => [
            'expected' => sprintf('{{ \'%s\' }}', self::TEST_BASE_PATH . self::TEST_GENERATED_ROUTE),
            'variable' => 'entity.url.view',
            'definition' => [
                'route' => 'route_view',
            ],
            'data' => [
                'entity' => new \stdClass(),
            ],
        ];
    }
    
    /**
     * @param string $expected
     * @param string $variable
     * @param array $definition
     * @param array $data
     *
     * @dataProvider  invalidDataProvider
     */
    public function testProcessWithWrongRoute($expected, $variable, array $definition, array $data = [])
    {
        $routeCollection = $this->createMock(RouteCollection::class);
        $routeCollection->expects($this->once())
            ->method('get')
            ->willReturn(null);
    
        $this->router->expects($this->once())->method('getRouteCollection')->willReturn($routeCollection);
    
        $this->assertEquals($expected, $this->processor->process($variable, $definition, $data));
    }
    
    /**
     * @param string $expected
     * @param string $variable
     * @param array $definition
     * @param array $data
     *
     * @dataProvider  invalidDataProvider
     */
    public function testProcessWithNonManageableEntity($expected, $variable, array $definition, array $data = [])
    {
        $routeCollection = $this->createMock(RouteCollection::class);
        $routeCollection->expects($this->once())
            ->method('get')
            ->willReturn(true);
    
        $this->router->expects($this->once())->method('getRouteCollection')->willReturn($routeCollection);
    
        $this->doctrineHelper->expects($this->once())->method('isManageableEntity')->willReturn(false);
    
        $this->assertEquals($expected, $this->processor->process($variable, $definition, $data));
    }
    
    /**
     * @param string $expected
     * @param string $variable
     * @param array $definition
     * @param array $data
     *
     * @dataProvider  invalidDataProvider
     */
    public function testProcessWithoutEntityConfig($expected, $variable, array $definition, array $data = [])
    {
        $routeCollection = $this->createMock(RouteCollection::class);
        $routeCollection->expects($this->once())
            ->method('get')
            ->willReturn(true);
    
        $this->router->expects($this->once())->method('getRouteCollection')->willReturn($routeCollection);
    
        $this->doctrineHelper->expects($this->once())->method('isManageableEntity')->willReturn(true);
    
        $this->extendConfigProvider->expects($this->once())->method('hasConfig')->willReturn(false);
    
        $this->assertEquals($expected, $this->processor->process($variable, $definition, $data));
    }

    /**
     * @param string $expected
     * @param string $variable
     * @param array $definition
     * @param array $data
     *
     * @dataProvider  invalidDataProvider
     */
    public function testProcessNonAccessibleEntity($expected, $variable, array $definition, array $data = [])
    {
        $routeCollection = $this->createMock(RouteCollection::class);
        $routeCollection->expects($this->once())
            ->method('get')
            ->willReturn(true);

        $this->router->expects($this->once())->method('getRouteCollection')->willReturn($routeCollection);

        $this->doctrineHelper->expects($this->once())->method('isManageableEntity')->willReturn(true);

        $this->extendConfigProvider->expects($this->once())->method('hasConfig')->willReturn(true);

        $configId = $this->createMock(ConfigIdInterface::class);
        $this->extendConfigProvider->expects($this->once())->method('getConfig')->willReturn(
            new Config($configId, [
                'is_extend' => true,
                'is_deleted' => true,
            ])
        );

        $this->assertEquals($expected, $this->processor->process($variable, $definition, $data));
    }

    public function invalidDataProvider()
    {
        yield 'sample' => [
            'expected' => '{{ \'entity.url.index\' }}',
            'variable' => 'entity.url.index',
            'definition' => [
                'route' => 'route_index',
            ],
            'data' => [
                'entity' => new \stdClass(),
            ],
        ];
    }
}
