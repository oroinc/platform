<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Oro\Bundle\DataGridBundle\Datagrid\TraceableManager;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\VarDumper\Caster\ClassStub;

class TraceableManagerTest extends TestCase
{
    private ConfigurationProviderInterface|MockObject $configurationProvider;
    private Builder|MockObject $builder;
    private RequestParameterBagFactory|MockObject $parametersFactory;
    private NameStrategyInterface|MockObject $nameStrategy;
    private RequestStack $requestStack;
    private TraceableManager $traceableManager;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->configurationProvider = $this->createMock(ConfigurationProviderInterface::class);
        $this->builder = $this->createMock(Builder::class);
        $this->parametersFactory = $this->createMock(RequestParameterBagFactory::class);
        $this->nameStrategy = $this->createMock(NameStrategyInterface::class);

        $this->traceableManager = new TraceableManager(
            $this->configurationProvider,
            $this->builder,
            $this->parametersFactory,
            $this->nameStrategy
        );

        $this->traceableManager->setRequestStack($this->requestStack);
    }

    public function testGetDatagrid(): void
    {
        $gridName = 'test_grid';
        $parameters = ['key' => 'val'];
        $parameterBag = new ParameterBag($parameters);
        $this->requestStack->push(new Request($parameters));

        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects(self::once())
            ->method('getPriority')
            ->willReturn(0);

        [$datagrid, $configuration] = $this->getDatagridMock($gridName, $parameterBag, [$extension]);

        $this->nameStrategy->expects(self::once())
            ->method('parseGridScope')
            ->willReturn('scope');
        $this->nameStrategy
            ->expects(self::once())
            ->method('parseGridName')
            ->with($gridName)
            ->willReturn($gridName);

        $this->configurationProvider->expects(self::once())
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($configuration);

        $this->builder
            ->expects(self::once())
            ->method('build')
            ->with($configuration, $parameterBag, [])
            ->willReturn($datagrid);

        self::assertSame($datagrid, $this->traceableManager->getDatagrid($gridName, $parameters));
    }

    public function testGetDatagridCalledTwiseWillNotSaveGridTwice(): void
    {
        $gridName = 'test_grid';
        $parameters = ['key' => 'val'];
        $parameterBag = new ParameterBag($parameters);
        $this->requestStack->push(new Request($parameters));
        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects(self::once())
            ->method('getPriority')
            ->willReturn(0);

        [$datagrid, $configuration] = $this->getDatagridMock($gridName, $parameterBag, [$extension]);
        $this->nameStrategy->expects(self::exactly(2))
            ->method('parseGridScope')
            ->willReturn('scope');
        $this->nameStrategy
            ->expects(self::exactly(2))
            ->method('parseGridName')
            ->with($gridName)
            ->willReturn($gridName);
        $this->configurationProvider->expects(self::exactly(2))
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($configuration);

        $this->builder
            ->expects(self::exactly(2))
            ->method('build')
            ->with($configuration, $parameterBag, [])
            ->willReturn($datagrid);

        self::assertSame($datagrid, $this->traceableManager->getDatagrid($gridName, $parameters));
        self::assertSame($datagrid, $this->traceableManager->getDatagrid($gridName, $parameters));
    }

    public function testGetDatagridByRequestParam(): void
    {
        $gridName = 'test_grid';
        $parameters = ['key' => 'val'];
        $parameterBag = new ParameterBag($parameters);
        $this->requestStack->push(new Request($parameters));
        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects(self::once())
            ->method('getPriority')
            ->willReturn(0);

        [$datagrid, $configuration] = $this->getDatagridMock($gridName, $parameterBag, [$extension]);
        $this->nameStrategy->expects(self::any())
            ->method('parseGridScope')
            ->willReturn('scope');
        $this->nameStrategy->expects(self::any())
            ->method('parseGridName')
            ->with($gridName)
            ->willReturn($gridName);
        $this->nameStrategy->expects(self::once())
            ->method('getGridUniqueName')
            ->with($gridName)
            ->willReturn($gridName);
        $this->parametersFactory->expects(self::any())
            ->method('createParameters')
            ->with($gridName)
            ->willReturn($parameterBag);
        $this->configurationProvider->expects(self::once())
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($configuration);

        $this->builder
            ->expects(self::once())
            ->method('build')
            ->with($configuration, $parameterBag, $parameters)
            ->willReturn($datagrid);

        self::assertSame($datagrid, $this->traceableManager->getDatagridByRequestParams($gridName));
    }

    public function testGetDatagrids(): void
    {
        $gridName = 'test_grid';
        $parameters = ['key' => 'val'];
        $gridKey = \json_encode([$parameters, []]);
        $parameterBag = new ParameterBag($parameters);
        $request = new Request($parameters);
        $this->requestStack->push($request);

        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects(self::once())
            ->method('getPriority')
            ->willReturn(0);

        [$datagrid, $configuration] = $this->getDatagridMock($gridName, $parameterBag, [$extension]);
        $this->nameStrategy->expects(self::any())
            ->method('parseGridScope')
            ->willReturn('scope');
        $this->nameStrategy->expects(self::any())
            ->method('parseGridName')
            ->with($gridName)
            ->willReturn($gridName);
        $this->parametersFactory->expects(self::any())
            ->method('createParameters')
            ->with($gridName)
            ->willReturn($parameterBag);
        $this->configurationProvider->expects(self::once())
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($configuration);

        $this->builder
            ->expects(self::once())
            ->method('build')
            ->with($configuration, $parameterBag, [])
            ->willReturn($datagrid);

        $this->traceableManager->getDatagrid($gridName, $parameters);

        self::assertEquals([
            $gridName => [
                $gridKey => [
                    'configuration' => [],
                    'resolved_metadata' => [],
                    'parameters' => $parameters,
                    'extensions' => [
                        [
                            'stub' => new ClassStub($extension::class),
                            'priority' => 0
                        ]
                    ],
                    'names' => [
                        $gridName
                    ]
                ]
            ]
        ], $this->traceableManager->getDatagrids($request));
    }

    public function testGetConfigurationForGrid(): void
    {
        $gridName = 'test_grid';
        $config = $this->createMock(DatagridConfiguration::class);

        $this->nameStrategy->expects(self::once())
            ->method('parseGridScope')
            ->willReturn('scope');
        $this->nameStrategy->expects(self::once())
            ->method('parseGridName')
            ->with($gridName)
            ->willReturn($gridName);
        $this->configurationProvider->expects(self::once())
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($config);

        self::assertSame($config, $this->traceableManager->getConfigurationForGrid($gridName));
    }

    private function getDatagridMock(
        string $name,
        ParameterBag $parameters,
        array $extensions = [],
    ): array {
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects(self::once())
            ->method('getName')
            ->willReturn($name);
        $config->expects(self::once())
            ->method('offsetGetOr')
            ->with(SystemAwareResolver::KEY_EXTENDED_FROM, [])
            ->willReturn([]);
        $config->expects(self::once())
            ->method('toArray')
            ->willReturn([]);

        $metadata = $this->createMock(MetadataObject::class);
        $metadata->expects(self::once())
            ->method('toArray')
            ->willReturn([]);

        $acceptor = $this->createMock(Acceptor::class);
        $acceptor->expects(self::once())
            ->method('getExtensions')
            ->willReturn($extensions);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::any())
            ->method('getName')
            ->willReturn($name);
        $datagrid->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $datagrid->expects(self::once())
            ->method('getResolvedMetadata')
            ->willReturn($metadata);
        $datagrid->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);
        $datagrid->expects(self::once())
            ->method('getAcceptor')
            ->willReturn($acceptor);

        return [$datagrid, $config];
    }
}
