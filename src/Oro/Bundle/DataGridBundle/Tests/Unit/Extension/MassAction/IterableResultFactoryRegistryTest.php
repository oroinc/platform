<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactoryInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactoryRegistry;
use PHPUnit\Framework\TestCase;

class IterableResultFactoryRegistryTest extends TestCase
{
    public function testCreateIterableResultWhenNoApplicableFactory(): void
    {
        $actionConfiguration = $this->createMock(ActionConfiguration::class);

        $gridConfiguration = $this->createMock(DatagridConfiguration::class);

        $selectedItems = SelectedItems::createFromParameters([]);

        $dataSource = new ArrayDatasource();

        $firstFactory = $this->createMock(IterableResultFactoryInterface::class);
        $firstFactory->expects($this->once())
            ->method('isApplicable')
            ->with($dataSource)
            ->willReturn(false);

        $secondFactory = $this->createMock(IterableResultFactoryInterface::class);
        $secondFactory->expects($this->once())
            ->method('isApplicable')
            ->with($dataSource)
            ->willReturn(false);

        $registry = new IterableResultFactoryRegistry([$firstFactory, $secondFactory]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('No IterableResultFactory found for "%s" datasource type', ArrayDatasource::class)
        );

        $registry->createIterableResult($dataSource, $actionConfiguration, $gridConfiguration, $selectedItems);
    }

    public function testCreateIterableResult(): void
    {
        $actionConfiguration = $this->createMock(ActionConfiguration::class);

        $gridConfiguration = $this->createMock(DatagridConfiguration::class);

        $selectedItems = SelectedItems::createFromParameters([]);

        $dataSource = $this->createMock(DatasourceInterface::class);

        $firstFactory = $this->createMock(IterableResultFactoryInterface::class);
        $firstFactory->expects($this->once())
            ->method('isApplicable')
            ->with($dataSource)
            ->willReturn(false);

        $secondFactory = $this->createMock(IterableResultFactoryInterface::class);
        $secondFactory->expects($this->once())
            ->method('isApplicable')
            ->with($dataSource)
            ->willReturn(true);

        $iterableResult = $this->createMock(IterableResultInterface::class);

        $secondFactory->expects($this->once())
            ->method('createIterableResult')
            ->with()
            ->willReturn($iterableResult);

        $registry = new IterableResultFactoryRegistry([$firstFactory, $secondFactory]);

        $this->assertSame(
            $iterableResult,
            $registry->createIterableResult($dataSource, $actionConfiguration, $gridConfiguration, $selectedItems)
        );
    }
}
