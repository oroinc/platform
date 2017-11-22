<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactoryInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactoryRegistry;

class IterableResultFactoryRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IterableResultFactoryRegistry
     */
    protected $iterableResultFactoryRegistry;

    protected function setUp()
    {
        $this->iterableResultFactoryRegistry = new IterableResultFactoryRegistry();
    }

    public function testCreateIterableResultWhenNoApplicableFactory()
    {
        /** @var ActionConfiguration|\PHPUnit_Framework_MockObject_MockObject $actionConfiguration **/
        $actionConfiguration = $this->createMock(ActionConfiguration::class);

        /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $gridConfiguration **/
        $gridConfiguration = $this->createMock(DatagridConfiguration::class);

        $selectedItems = SelectedItems::createFromParameters([]);

        $dataSource = new ArrayDatasource();

        /** @var IterableResultFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $firstFactory **/
        $firstFactory = $this->createMock(IterableResultFactoryInterface::class);
        $firstFactory
            ->expects($this->once())
            ->method('isApplicable')
            ->with($dataSource)
            ->willReturn(false);

        /** @var IterableResultFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $secondFactory **/
        $secondFactory = $this->createMock(IterableResultFactoryInterface::class);
        $secondFactory
            ->expects($this->once())
            ->method('isApplicable')
            ->with($dataSource)
            ->willReturn(false);

        $this->iterableResultFactoryRegistry->addFactory($firstFactory);
        $this->iterableResultFactoryRegistry->addFactory($secondFactory);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('No IterableResultFactory found for "%s" datasource type', ArrayDatasource::class)
        );

        $this->iterableResultFactoryRegistry
            ->createIterableResult($dataSource, $actionConfiguration, $gridConfiguration, $selectedItems);
    }

    public function testCreateIterableResult()
    {
        /** @var ActionConfiguration|\PHPUnit_Framework_MockObject_MockObject $actionConfiguration **/
        $actionConfiguration = $this->createMock(ActionConfiguration::class);

        /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $gridConfiguration **/
        $gridConfiguration = $this->createMock(DatagridConfiguration::class);

        $selectedItems = SelectedItems::createFromParameters([]);

        /** @var DatasourceInterface|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this->createMock(DatasourceInterface::class);

        /** @var IterableResultFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $firstFactory **/
        $firstFactory = $this->createMock(IterableResultFactoryInterface::class);
        $firstFactory
            ->expects($this->once())
            ->method('isApplicable')
            ->with($dataSource)
            ->willReturn(false);

        /** @var IterableResultFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $secondFactory **/
        $secondFactory = $this->createMock(IterableResultFactoryInterface::class);
        $secondFactory
            ->expects($this->once())
            ->method('isApplicable')
            ->with($dataSource)
            ->willReturn(true);

        /** @var IterableResultInterface|\PHPUnit_Framework_MockObject_MockObject $iterableResult */
        $iterableResult = $this->createMock(IterableResultInterface::class);

        $secondFactory
            ->expects($this->once())
            ->method('createIterableResult')
            ->with()
            ->willReturn($iterableResult);

        $this->iterableResultFactoryRegistry->addFactory($firstFactory);
        $this->iterableResultFactoryRegistry->addFactory($secondFactory);

        $this->assertSame(
            $iterableResult,
            $this->iterableResultFactoryRegistry
                ->createIterableResult($dataSource, $actionConfiguration, $gridConfiguration, $selectedItems)
        );
    }
}
