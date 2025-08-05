<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\TraceableManager;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\VarDumper\Caster\ClassStub;

class TraceableManagerTest extends TestCase
{
    private ManagerInterface|MockObject $innerManager;
    private RequestStack $requestStack;
    private TraceableManager $traceableManager;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->innerManager = $this->createMock(ManagerInterface::class);
        $this->traceableManager = new TraceableManager($this->innerManager, $this->requestStack);
    }

    public function testGetDatagrid(): void
    {
        $gridName = 'test_grid';
        $parameters = ['key' => 'val'];
        $this->requestStack->push(new Request($parameters));
        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects(self::once())
            ->method('getPriority')
            ->willReturn(0);

        $datagrid = $this->getDatagridMock($gridName, $parameters, [$extension]);
        $this->innerManager
            ->expects(self::once())
            ->method('getDatagrid')
            ->with($gridName, $parameters, [])
            ->willReturn($datagrid);

        self::assertSame($datagrid, $this->traceableManager->getDatagrid($gridName, $parameters, []));
    }

    public function testGetDatagridCalledTwiseWillNotSaveGridTwice(): void
    {
        $gridName = 'test_grid';
        $parameters = ['key' => 'val'];
        $this->requestStack->push(new Request($parameters));

        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects(self::once())
            ->method('getPriority')
            ->willReturn(0);

        $datagrid = $this->getDatagridMock($gridName, $parameters, [$extension]);

        $this->innerManager
            ->expects(self::exactly(2))
            ->method('getDatagrid')
            ->with($gridName, $parameters, [])
            ->willReturn($datagrid);

        self::assertSame($datagrid, $this->traceableManager->getDatagrid($gridName, $parameters, []));
        self::assertSame($datagrid, $this->traceableManager->getDatagrid($gridName, $parameters, []));
    }

    public function testGetDatagridByRequestParam(): void
    {
        $gridName = 'test_grid';
        $parameters = [];
        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects(self::once())
            ->method('getPriority')
            ->willReturn(0);

        $datagrid = $this->getDatagridMock($gridName, $parameters, [$extension]);
        $this->innerManager
            ->expects(self::once())
            ->method('getDatagridByRequestParams')
            ->with($gridName, [])
            ->willReturn($datagrid);

        self::assertSame($datagrid, $this->traceableManager->getDatagridByRequestParams($gridName, []));
    }

    public function testGetDatagrids(): void
    {
        $gridName = 'test_grid';
        $parameters = ['key' => 'val'];
        $gridKey = \json_encode([$parameters, []]);
        $request = new Request($parameters);
        $this->requestStack->push($request);

        $extension = $this->createMock(ExtensionVisitorInterface::class);
        $extension->expects(self::once())
            ->method('getPriority')
            ->willReturn(0);

        $datagrid = $this->getDatagridMock($gridName, $parameters, [$extension]);

        $this->innerManager
            ->expects(self::once())
            ->method('getDatagrid')
            ->with($gridName, $parameters, [])
            ->willReturn($datagrid);

        $this->traceableManager->getDatagrid($gridName, $parameters, []);

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
        $this->innerManager->expects(self::once())
            ->method('getConfigurationForGrid')
            ->willReturn($config);

        self::assertSame($config, $this->traceableManager->getConfigurationForGrid($gridName));
    }

    private function getDatagridMock($name, $parameters = [], array $extensions = []): DatagridInterface|MockObject
    {
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
            ->willReturn(new ParameterBag($parameters));
        $datagrid->expects(self::once())
            ->method('getAcceptor')
            ->willReturn($acceptor);

        return $datagrid;
    }
}
