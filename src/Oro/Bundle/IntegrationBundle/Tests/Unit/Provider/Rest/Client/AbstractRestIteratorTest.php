<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\AbstractRestIterator;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

class AbstractRestIteratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RestClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var AbstractRestIterator|\PHPUnit\Framework\MockObject\MockObject */
    private $iterator;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RestClientInterface::class);
        $this->iterator = $this->getMockBuilder(AbstractRestIterator::class)
            ->setConstructorArgs([$this->client])
            ->getMockForAbstractClass();
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorForeach(array $expectations, array $expectedItems)
    {
        $this->expectIteratorCalls($expectations, count($expectedItems) > 0);

        $actualItems = [];
        foreach ($this->iterator as $key => $value) {
            $actualItems[$key] = $value;
        }

        $this->assertEquals($expectedItems, $actualItems);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorWhile(array $expectations, array $expectedItems)
    {
        $this->expectIteratorCalls($expectations, count($expectedItems) > 0);

        $actualItems = [];
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        $this->assertEquals($expectedItems, $actualItems);
    }

    /**
     * @dataProvider iteratorDataProvider
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testIterateTwice(array $expectations, array $expectedItems)
    {
        $loadPageReturns = [];
        $rowsDataParameters = [];
        $rowsDataReturns = [];
        $totalCountDataParameters = [];
        $totalCountDataReturns = [];
        for ($i = 0; $i < 2; $i++) {
            foreach ($expectations as $data) {
                $expectedData = $data['data'] ? ['foo' => 'bar'] : null;
                $loadPageReturns[] = $expectedData;
                if ($data['data']) {
                    $rowsDataParameters[] = [$expectedData];
                    $rowsDataReturns[] = $data['rows'];
                    $totalCountDataParameters[] = [$expectedData];
                    $totalCountDataReturns[] = $data['totalCount'];
                }
            }
            if (count($expectedItems) > 0) {
                $loadPageReturns[] = false;
            }
        }
        $this->setIteratorExpects(
            $loadPageReturns,
            $rowsDataParameters,
            $rowsDataReturns,
            $totalCountDataParameters,
            $totalCountDataReturns
        );

        $actualItems = [];
        $this->iterator->rewind();
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }
        $this->assertEquals($expectedItems, $actualItems);

        $actualItems = [];
        $this->iterator->rewind();
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }
        $this->assertEquals($expectedItems, $actualItems);
    }

    public function iteratorDataProvider(): array
    {
        return [
            'two pages, 7 records' => [
                'expectations'  => [
                    [
                        'data'       => true,
                        'rows'       => [
                            ['id' => 1],
                            ['id' => 2],
                            ['id' => 3],
                            ['id' => 4],
                        ],
                        'totalCount' => 7,
                    ],
                    [
                        'data'       => true,
                        'rows'       => [
                            ['id' => 5],
                            ['id' => 6],
                            ['id' => 7],
                        ],
                        'totalCount' => 7,
                    ],
                ],
                'expectedItems' => [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
                    ['id' => 4],
                    ['id' => 5],
                    ['id' => 6],
                    ['id' => 7],
                ],
            ],
            'empty results'        => [
                'expectations'  => [
                    [
                        'data'       => true,
                        'rows'       => [],
                        'totalCount' => 0,
                    ]
                ],
                'expectedItems' => []
            ],
            'empty response'       => [
                'expectations'  => [
                    [
                        'data' => false,
                    ]
                ],
                'expectedItems' => []
            ],
        ];
    }

    /**
     * @dataProvider countDataProvider
     */
    public function testCount(array $expectations, $expectedCount)
    {
        $this->expectIteratorCalls($expectations);

        $this->assertEquals($expectedCount, $this->iterator->count());
    }

    public function countDataProvider(): array
    {
        return [
            'normal'         => [
                'expectations'  => [
                    [
                        'data'       => true,
                        'rows'       => [
                            ['id' => 1],
                            ['id' => 2],
                            ['id' => 3],
                            ['id' => 4],
                        ],
                        'totalCount' => 7,
                    ],
                ],
                'expectedCount' => 7,
            ],
            'empty response' => [
                'expectations'  => [
                    [
                        'data' => false,
                    ],
                ],
                'expectedCount' => 0,
            ],
        ];
    }

    private function expectIteratorCalls(array $expectations, bool $loadLastPage = false): void
    {
        $loadPageReturns = [];
        $rowsDataParameters = [];
        $rowsDataReturns = [];
        $totalCountDataParameters = [];
        $totalCountDataReturns = [];
        foreach ($expectations as $data) {
            $expectedData = $data['data'] ? ['foo' => 'bar'] : null;
            $loadPageReturns[] = $expectedData;
            if ($data['data']) {
                $rowsDataParameters[] = [$expectedData];
                $rowsDataReturns[] = $data['rows'];
                $totalCountDataParameters[] = [$expectedData];
                $totalCountDataReturns[] = $data['totalCount'];
            }
        }
        if ($loadLastPage) {
            $loadPageReturns[] = false;
        }
        $this->setIteratorExpects(
            $loadPageReturns,
            $rowsDataParameters,
            $rowsDataReturns,
            $totalCountDataParameters,
            $totalCountDataReturns
        );
    }

    private function setIteratorExpects(
        array $loadPageReturns,
        array $rowsDataParameters,
        array $rowsDataReturns,
        array $totalCountDataParameters,
        array $totalCountDataReturns
    ): void {
        $this->iterator->expects($this->exactly(count($loadPageReturns)))
            ->method('loadPage')
            ->with($this->client)
            ->willReturnOnConsecutiveCalls(...$loadPageReturns);
        if ($rowsDataParameters) {
            $this->iterator->expects($this->exactly(count($rowsDataParameters)))
                ->method('getRowsFromPageData')
                ->withConsecutive(...$rowsDataParameters)
                ->willReturnOnConsecutiveCalls(...$rowsDataReturns);
        }
        if ($totalCountDataParameters) {
            $this->iterator->expects($this->exactly(count($totalCountDataParameters)))
                ->method('getTotalCountFromPageData')
                ->withConsecutive(...$totalCountDataParameters)
                ->willReturnOnConsecutiveCalls(...$totalCountDataReturns);
        }
    }
}
