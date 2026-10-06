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

        $datagrid = $this->getDatagridMock($gridName);
        $this->innerManager
            ->expects(self::once())
            ->method('getDatagrid')
            ->with($gridName, $parameters, [])
            ->willReturn($datagrid);

        self::assertSame($datagrid, $this->traceableManager->getDatagrid($gridName, $parameters, []));
    }

    public function testGetDatagridCalledTwiceKeepsOneDatagridPerParameters(): void
    {
        $gridName = 'test_grid';
        $parameters = ['key' => 'val'];
        $request = new Request($parameters);
        $this->requestStack->push($request);

        $firstDatagrid = $this->getDatagridMock($gridName);
        $lastDatagrid = $this->getDatagridMock($gridName, $parameters, [], true);

        $this->innerManager
            ->expects(self::exactly(2))
            ->method('getDatagrid')
            ->with($gridName, $parameters, [])
            ->willReturnOnConsecutiveCalls($firstDatagrid, $lastDatagrid);

        self::assertSame($firstDatagrid, $this->traceableManager->getDatagrid($gridName, $parameters, []));
        self::assertSame($lastDatagrid, $this->traceableManager->getDatagrid($gridName, $parameters, []));

        $datagrids = $this->traceableManager->getDatagrids($request);
        self::assertCount(1, $datagrids[$gridName]);
    }

    public function testGetDatagridByRequestParam(): void
    {
        $gridName = 'test_grid';

        $datagrid = $this->getDatagridMock($gridName);
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

        $datagrid = $this->getDatagridMock($gridName, $parameters, [$extension], true);

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

    /**
     * The datagrid is described only when the traced datagrids are read; tracing itself never touches
     * its metadata, so the application is the first to visit it.
     */
    private function getDatagridMock(
        string $name,
        array $parameters = [],
        array $extensions = [],
        bool $described = false
    ): DatagridInterface|MockObject {
        $describedTimes = $described ? self::once() : self::never();

        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects(clone $describedTimes)
            ->method('getName')
            ->willReturn($name);
        $config->expects(clone $describedTimes)
            ->method('offsetGetOr')
            ->with(SystemAwareResolver::KEY_EXTENDED_FROM, [])
            ->willReturn([]);
        $config->expects(clone $describedTimes)
            ->method('toArray')
            ->willReturn([]);

        $metadata = $this->createMock(MetadataObject::class);
        $metadata->expects(clone $describedTimes)
            ->method('toArray')
            ->willReturn([]);

        $acceptor = $this->createMock(Acceptor::class);
        $acceptor->expects(clone $describedTimes)
            ->method('getExtensions')
            ->willReturn($extensions);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::any())
            ->method('getName')
            ->willReturn($name);
        $datagrid->expects(clone $describedTimes)
            ->method('getConfig')
            ->willReturn($config);
        $datagrid->expects(clone $describedTimes)
            ->method('getMetadata')
            ->willReturn($metadata);
        $datagrid->expects(self::never())
            ->method('getResolvedMetadata');
        $datagrid->expects(clone $describedTimes)
            ->method('getParameters')
            ->willReturn(new ParameterBag($parameters));
        $datagrid->expects(clone $describedTimes)
            ->method('getAcceptor')
            ->willReturn($acceptor);

        return $datagrid;
    }
}
