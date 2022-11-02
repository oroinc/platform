<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Pager\ArrayDatasource;

use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Extension\Pager\ArrayDatasource\ArrayPager;

class ArrayPagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ArrayPager */
    private $arrayPager;

    protected function setUp(): void
    {
        $this->arrayPager = new ArrayPager();
    }

    /**
     * @dataProvider pageDataProvider
     */
    public function testApply(int $currentPage, int $maxPerPage, int $arrayLength, int $expectedLength)
    {
        $this->arrayPager->setPage($currentPage);
        $this->arrayPager->setMaxPerPage($maxPerPage);

        $datasource = new ArrayDatasource();
        $datasource->setArraySource($this->prepareResults($arrayLength));

        $this->arrayPager->apply($datasource);

        $this->assertCount($expectedLength, $datasource->getResults());
    }

    public function pageDataProvider(): array
    {
        return [
            ['currentPage' => 1, 'maxPerPage' => 25, 'arrayLength' => 50, 'expectedLength' => 25],
            ['currentPage' => 1, 'maxPerPage' => 25, 'arrayLength' => 10, 'expectedLength' => 10],
            ['currentPage' => 8, 'maxPerPage' => 10, 'arrayLength' => 76, 'expectedLength' => 6],
            ['currentPage' => 1, 'maxPerPage' => 100, 'arrayLength' => 25, 'expectedLength' => 25],
        ];
    }

    private function prepareResults(int $rowsCount = 50): array
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
