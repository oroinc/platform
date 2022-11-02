<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\GridEventInterface;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BuilderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_DATASOURCE_TYPE = 'array';
    private const TEST_DATAGRID_NAME   = 'testGrid';

    private const DEFAULT_DATAGRID_CLASS = Datagrid::class;
    private const DEFAULT_ACCEPTOR_CLASS = Acceptor::class;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    protected function setUp(): void
    {
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @dataProvider buildProvider
     */
    public function testBuild(
        DatagridConfiguration $config,
        string $resultFQCN,
        array $raisedEvents,
        int $extensionsCount,
        array $extensionsMocks = [],
        array $minifiedParams = [],
        array $additionalParams = []
    ): void {
        $config->setDatasourceType(self::TEST_DATASOURCE_TYPE);
        $builder = $this->getBuilder(
            [self::TEST_DATASOURCE_TYPE => $this->createMock(DatasourceInterface::class)],
            $extensionsMocks
        );
        $parameters = $this->createMock(ParameterBag::class);

        $parameters->expects($this->once())
            ->method('get')
            ->with(ParameterBag::MINIFIED_PARAMETERS)
            ->willReturn($minifiedParams);

        if (is_array($minifiedParams) && array_key_exists('g', $minifiedParams) && is_array($minifiedParams['g'])) {
            $parameters->expects($this->once())
                ->method('add')
                ->with(array_merge($minifiedParams['g'], $additionalParams));
        } else {
            $parameters->expects($this->never())
                ->method('add');
        }

        $with = [];
        $will = [];
        foreach ($raisedEvents as [$name, $eventType]) {
            $with[] = [$this->anything(), $name];
            $will[] = new ReturnCallback(function ($event) use ($eventType, $resultFQCN) {
                $this->assertInstanceOf($eventType, $event);
                if ($event instanceof GridEventInterface) {
                    $this->assertInstanceOf($resultFQCN, $event->getDatagrid());
                }

                return $event;
            });
        }
        $this->eventDispatcher->expects($this->exactly(count($raisedEvents)))
            ->method('dispatch')
            ->withConsecutive(...$with)
            ->willReturnOnConsecutiveCalls(...$will);

        $result = $builder->build($config, $parameters, $additionalParams);
        $this->assertInstanceOf($resultFQCN, $result);

        $this->assertInstanceOf(self::DEFAULT_ACCEPTOR_CLASS, $result->getAcceptor());

        $this->assertCount($extensionsCount, $result->getAcceptor()->getExtensions());
    }

    public function buildProvider(): array
    {
        $stubDatagridClass = Datagrid::class;
        $baseEventList = [
            ['oro_datagrid.datagrid.build.pre', PreBuild::class],
            ['oro_datagrid.datagrid.build.before', BuildBefore::class],
            ['oro_datagrid.datagrid.build.after', BuildAfter::class],
        ];

        return [
            'Base datagrid should be created without extensions'         => [
                DatagridConfiguration::createNamed(self::TEST_DATAGRID_NAME, []),
                self::DEFAULT_DATAGRID_CLASS,
                $baseEventList,
                0
            ],
            'Datagrid should be created as object type passed in config' => [
                DatagridConfiguration::createNamed(
                    self::TEST_DATAGRID_NAME,
                    ['options' => ['base_datagrid_class' => $stubDatagridClass]]
                ),
                $stubDatagridClass,
                $baseEventList,
                0
            ],
            'Extension passed check'                                     => [
                DatagridConfiguration::createNamed(self::TEST_DATAGRID_NAME, []),
                self::DEFAULT_DATAGRID_CLASS,
                $baseEventList,
                1,
                [
                    $this->getExtensionVisitor(),
                    $this->getExtensionVisitor(false)
                ]
            ],
            'Both extensions passed check'                               => [
                DatagridConfiguration::createNamed(self::TEST_DATAGRID_NAME, []),
                self::DEFAULT_DATAGRID_CLASS,
                $baseEventList,
                2,
                [
                    $this->getExtensionVisitor(),
                    $this->getExtensionVisitor(false),
                    $this->getExtensionVisitor()
                ]
            ],
            'With minified parameters without grid params'               => [
                DatagridConfiguration::createNamed(self::TEST_DATAGRID_NAME, []),
                self::DEFAULT_DATAGRID_CLASS,
                $baseEventList,
                0,
                [],
                ['i' => '1', 'p' => '25']
            ],
            'With minified parameters with grid params'                  => [
                DatagridConfiguration::createNamed(self::TEST_DATAGRID_NAME, []),
                self::DEFAULT_DATAGRID_CLASS,
                $baseEventList,
                0,
                [],
                ['g' => ['class_name' => 'Extended_Entity_Test']],
                ['additional' => 'param']
            ]
        ];
    }

    /**
     * @dataProvider buildDatasourceProvider
     */
    public function testBuildDatasource(
        DatagridConfiguration $config,
        array $dataSources,
        ?array $expectedException,
        bool $processCallExpects = false
    ): void {
        $builder = $this->getBuilder($dataSources);
        $grid = $this->createMock(DatagridInterface::class);

        foreach ($dataSources as $obj) {
            if ($processCallExpects) {
                $obj->expects($this->once())
                    ->method('process')
                    ->with($grid);
            }
        }

        if ($expectedException !== null) {
            [$name, $message] = $expectedException;

            $this->expectException($name);
            $this->expectExceptionMessage($message);
        }

        ReflectionUtil::callMethod($builder, 'buildDataSource', [$grid, $config]);
    }

    public function buildDatasourceProvider(): array
    {
        return [
            'Datasource not configured, exceptions should be thrown' => [
                DatagridConfiguration::create([]),
                [],
                [\RuntimeException::class, 'Datagrid source does not configured']
            ],
            'Configured datasource does not exist'                   => [
                DatagridConfiguration::create(['source' => ['type' => self::TEST_DATASOURCE_TYPE]]),
                [],
                [\RuntimeException::class, sprintf('Datagrid source "%s" does not exist', self::TEST_DATASOURCE_TYPE)]
            ],
            'Configured correct and allowed'                         => [
                DatagridConfiguration::create(['source' => ['type' => self::TEST_DATASOURCE_TYPE]]),
                [self::TEST_DATASOURCE_TYPE => $this->createMock(DatasourceInterface::class)],
                null,
                true
            ]
        ];
    }

    private function getBuilder(array $dataSources = [], array $extensions = []): Builder
    {
        $dataSourceContainerBuilder = TestContainerBuilder::create();
        foreach ($dataSources as $name => $dataSource) {
            $dataSourceContainerBuilder->add($name, $dataSource);
        }

        $builder = new Builder(
            self::DEFAULT_DATAGRID_CLASS,
            self::DEFAULT_ACCEPTOR_CLASS,
            $this->eventDispatcher,
            $dataSourceContainerBuilder->getContainer($this),
            $extensions
        );
        $builder->setMemoryCacheProvider($this->memoryCacheProvider);

        return $builder;
    }

    private function getExtensionVisitor(bool $isApplicable = true): ExtensionVisitorInterface
    {
        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects($this->any())
            ->method('isApplicable')
            ->willReturn($isApplicable);

        return $extension;
    }
}
