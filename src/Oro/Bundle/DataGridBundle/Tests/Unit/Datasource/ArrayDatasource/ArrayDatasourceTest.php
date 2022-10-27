<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\ArrayDatasource;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ArrayDatasourceTest extends \PHPUnit\Framework\TestCase
{
    private $arraySource = [
        [
            'priceListId' => 1,
            'priceListName' => 'PriceList 1',
            'quantity' => 2,
            'unitCode' => 'item',
            'value' => '2221',
            'currency' => 'USD',
        ],
        [
            'priceListId' => 2,
            'priceListName' => 'PriceList 2',
            'quantity' => 3,
            'unitCode' => 'item',
            'value' => '3233',
            'currency' => 'USD',
        ],
        [
            'priceListId' => 3,
            'priceListName' => 'PriceList 3',
            'quantity' => 4,
            'unitCode' => 'item',
            'value' => '323',
            'currency' => 'GBP',
        ],
        [
            'priceListId' => 4,
            'priceListName' => 'PriceList 4',
            'quantity' => 5,
            'unitCode' => 'item',
            'value' => '35',
            'currency' => 'EURO',
        ],
    ];

    /** @var ArrayDatasource */
    private $arrayDatasource;

    protected function setUp(): void
    {
        $this->arrayDatasource = new ArrayDatasource();
    }

    public function testProcess()
    {
        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->once())
            ->method('setDatasource')
            ->with(clone $this->arrayDatasource);
        $this->arrayDatasource->process($grid, []);
    }

    public function testGetResultsByQuantity()
    {
        $this->arrayDatasource->setArraySource($this->arraySource);

        $this->assertSameSize($this->arraySource, $this->arrayDatasource->getResults());
    }

    public function testResultsByType()
    {
        $this->arrayDatasource->setArraySource(reset($this->arraySource));
        $result = $this->arrayDatasource->getResults();

        $this->assertInstanceOf(ResultRecordInterface::class, $result[0]);
    }

    public function testSetArraySource()
    {
        $this->arrayDatasource->setArraySource($this->arraySource);

        $this->assertEquals($this->arraySource, $this->arrayDatasource->getArraySource());
    }
}
