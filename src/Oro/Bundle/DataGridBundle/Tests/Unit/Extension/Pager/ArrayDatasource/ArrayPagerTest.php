<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Pager\ArrayDatasource;

use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Extension\Pager\ArrayDatasource\ArrayPager;

class ArrayPagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ArrayPager */
    protected $arrayPager;

    protected function setUp()
    {
        $this->arrayPager = new ArrayPager();
    }

    /**
     * @dataProvider pageDataProvider
     * @param int $currentPage
     * @param int $maxPerPage
     * @param int $arrayLength
     * @param int $expectedLength
     */
    public function testApply($currentPage, $maxPerPage, $arrayLength, $expectedLength)
    {
        $this->arrayPager->setPage($currentPage);
        $this->arrayPager->setMaxPerPage($maxPerPage);

        $datasource = new ArrayDatasource();
        $datasource->setArraySource($this->prepareResults($arrayLength));

        $this->arrayPager->apply($datasource);

        $this->assertCount($expectedLength, $datasource->getResults());
    }

    public function pageDataProvider()
    {
        return [
            ['currentPage' => 1, 'maxPerPage' => 25, 'arrayLength' => 50, 'expectedLength' => 25],
            ['currentPage' => 1, 'maxPerPage' => 25, 'arrayLength' => 10, 'expectedLength' => 10],
            ['currentPage' => 8, 'maxPerPage' => 10, 'arrayLength' => 76, 'expectedLength' => 6],
            ['currentPage' => 1, 'maxPerPage' => 100, 'arrayLength' => 25, 'expectedLength' => 25],
        ];
    }

    /**
     * @param int $rowsCount
     * @return array
     */
    protected function prepareResults($rowsCount = 50)
    {
        $arraySource  = [];

        $rowData =  [
            'priceListId' => 256,
            'priceListName' => 'A'
        ];

        for ($i = 1; $i<=$rowsCount; $i++) {
            $arraySource[] = $rowData;
        }

        return $arraySource;
    }
}
