<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Extension\MassAction\IterableResultFactory;

class IterableResultFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IterableResultFactory
     */
    protected $iterableResultFactory;

    protected function setUp()
    {
        $this->iterableResultFactory = new IterableResultFactory();
    }

    public function testIsApplicableWhenNotApplicable()
    {
        static::assertFalse($this->iterableResultFactory->isApplicable(new ArrayDatasource()));
    }

    public function testIsApplicable()
    {
        /** @var SearchDatasource $datasource */
        $datasource = $this->createMock(SearchDatasource::class);

        static::assertTrue($this->iterableResultFactory->isApplicable($datasource));
    }

    public function testCreateIterableResultWhenDatasourceNotSupported()
    {
        /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject $gridConfiguration **/
        $gridConfiguration = $this->createMock(DatagridConfiguration::class);
        $selectedItems = SelectedItems::createFromParameters([]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Expecting "%s" datasource type, "%s" given', SearchDatasource::class, ArrayDatasource::class)
        );

        $this->iterableResultFactory->createIterableResult(
            new ArrayDatasource(),
            ActionConfiguration::create([]),
            $gridConfiguration,
            $selectedItems
        );
    }
}
