<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

class AbstractRestIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $iterator;

    protected function setUp()
    {
        $this->client = $this->getMock('Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Client\\RestClientInterface');
        $this->iterator = $this->transport = $this->getMockBuilder(
            'Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Client\\AbstractRestIterator'
        )->setConstructorArgs([$this->client])
            ->getMockForAbstractClass();
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorForeach(array $expectations, array $expectedItems)
    {
        $this->expectIteratorCalls($expectations);

        $actualItems = array();

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
        $this->expectIteratorCalls($expectations);

        $actualItems = array();

        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        $this->assertEquals($expectedItems, $actualItems);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIterateTwice(array $expectations, array $expectedItems)
    {
        $this->expectIteratorCalls($expectations);

        $actualItems = array();

        $this->iterator->rewind();
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        $this->assertEquals($expectedItems, $actualItems);

        $this->expectIteratorCalls($expectations);

        $actualItems = array();

        $this->iterator->rewind();
        while ($this->iterator->valid()) {
            $actualItems[$this->iterator->key()] = $this->iterator->current();
            $this->iterator->next();
        }

        $this->assertEquals($expectedItems, $actualItems);
    }

    public function iteratorDataProvider()
    {
        return [
            'two pages, 7 records' => [
                'expectations' => [
                    [
                        'data' => true,
                        'rows' => [
                            ['id' => 1],
                            ['id' => 2],
                            ['id' => 3],
                            ['id' => 4],
                        ],
                        'totalCount' => 7,
                    ],
                    [
                        'data' => true,
                        'rows' => [
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
            'empty results' => [
                'expectations' => [
                    [
                        'data' => true,
                        'rows' => [],
                        'totalCount' => 0,
                    ]
                ],
                'expectedItems' => []
            ],
            'empty response' => [
                'expectations' => [
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

    public function countDataProvider()
    {
        return array(
            'normal' => array(
                'expectations' => [
                    [
                        'data' => true,
                        'rows' => [
                            ['id' => 1],
                            ['id' => 2],
                            ['id' => 3],
                            ['id' => 4],
                        ],
                        'totalCount' => 7,
                    ],
                ],
                'expectedCount' => 7,
            ),
            'empty response' => array(
                'expectations' => [
                    [
                        'data' => false,
                    ],
                ],
                'expectedCount' => 0,
            ),
        );
    }

    /**
     * @param array $expectations
     * @return int
     */
    protected function expectIteratorCalls(array $expectations)
    {
        $index = 0;
        foreach ($expectations as $data) {
            $expectedData = $data['data'] ? ['foo' => 'bar'] : null;

            $this->iterator->expects($this->at($index++))
                ->method('loadPage')
                ->with($this->client)
                ->will($this->returnValue($expectedData));

            if ($data['data']) {
                $this->iterator->expects($this->at($index++))
                    ->method('getRowsFromPageData')
                    ->with($expectedData)
                    ->will($this->returnValue($data['rows']));

                $this->iterator->expects($this->at($index++))
                    ->method('getTotalCountFromPageData')
                    ->with($expectedData)
                    ->will($this->returnValue($data['totalCount']));
            }
        }
    }
}
