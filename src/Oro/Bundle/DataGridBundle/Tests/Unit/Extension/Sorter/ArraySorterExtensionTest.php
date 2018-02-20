<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Sorter\ArraySorterExtension;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration;

class ArraySorterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ArraySorterExtension */
    protected $arraySorterExtension;

    /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $config **/
    protected $config;

    /** @var  ArrayDatasource */
    protected $arrayDatasource;

    /**
     * @var array
     */
    protected $arraySource = [
        [
            'priceListId' => 256,
            'priceListName' => 'A',
        ],
        [
            'priceListId' => 5,
            'priceListName' => 'B',
        ],
        [
            'priceListId' => 34,
            'priceListName' => 'C',
        ],
        [
            'priceListId' => 41,
            'priceListName' => 'D',
        ],
    ];

    protected function setUp()
    {
        $this->config = $this->createMock(DatagridConfiguration::class);
        $this->arrayDatasource = new ArrayDatasource();
        $this->arrayDatasource->setArraySource($this->arraySource);
        $this->arraySorterExtension = new ArraySorterExtension();
        $this->arraySorterExtension->setParameters(new ParameterBag());
    }

    public function testIsApplicableWithArrayDatasource()
    {
        $this->config->expects($this->once())->method('getDatasourceType')
            ->willReturn(ArrayDatasource::TYPE);

        $this->config->expects($this->once())->method('offsetGetByPath')
            ->with(Configuration::COLUMNS_PATH)->willReturn([]);

        $this->assertTrue($this->arraySorterExtension->isApplicable($this->config));
    }

    public function testIsApplicableWithWrongDatasource()
    {
        $this->config->expects($this->once())->method('getDatasourceType')
            ->willReturn(OrmDatasource::TYPE);

        $this->config->expects($this->never())->method('offsetGetByPath')
            ->with(Configuration::COLUMNS_PATH)->willReturn([]);

        $this->assertFalse($this->arraySorterExtension->isApplicable($this->config), new ArrayDatasource());
    }

    /**
     * @dataProvider sortingDataProvider
     * @param $sorter
     * @param $defaultSorter
     * @param $expectedData
     */
    public function testVisitDatasource($sorter, $defaultSorter, $expectedData)
    {
        $this->config->expects($this->at(0))->method('offsetGetByPath')
            ->with(Configuration::COLUMNS_PATH)->willReturn($sorter);

        $this->config->expects($this->at(1))->method('offsetGetByPath')
            ->with(Configuration::DEFAULT_SORTERS_PATH, [])->willReturn($defaultSorter);

        $this->config->expects($this->at(2))->method('offsetGetByPath')
            ->with(Configuration::DISABLE_DEFAULT_SORTING_PATH, false)->willReturn([]);

        $this->arraySorterExtension->setParameters(new ParameterBag());
        $this->arraySorterExtension->visitDatasource($this->config, $this->arrayDatasource);

        $this->assertEquals($expectedData, $this->arrayDatasource->getArraySource());
    }

    /** @expectedException \Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException */
    public function testVisitDatasourceWithWrongDatasourceType()
    {
        $this->config->expects($this->at(0))->method('offsetGetByPath')
            ->with(Configuration::COLUMNS_PATH)
            ->willReturn(['priceListName' => ['data_name' => 'priceListName']]);

        $this->config->expects($this->at(1))->method('offsetGetByPath')
            ->with(Configuration::DEFAULT_SORTERS_PATH, [])->willReturn(['priceListName' => 'DESC']);

        $this->config->expects($this->at(2))->method('offsetGetByPath')
            ->with(Configuration::DISABLE_DEFAULT_SORTING_PATH, false)->willReturn([]);

        $this->arraySorterExtension->setParameters(new ParameterBag());
        $this->arraySorterExtension->visitDatasource(
            $this->config,
            $this->createMock(OrmDatasource::class)
        );
    }

    /**
     * @return array
     */
    public function sortingDataProvider()
    {
        return [
            [
                'sorter' => ['priceListName' => ['data_name' => 'priceListName']],
                'defaultSorting' => ['priceListName' => 'ASC'],
                'expectedData' => [
                    [
                        'priceListId' => 256,
                        'priceListName' => 'A',
                    ],
                    [
                        'priceListId' => 5,
                        'priceListName' => 'B',
                    ],
                    [
                        'priceListId' => 34,
                        'priceListName' => 'C',
                    ],
                    [
                        'priceListId' => 41,
                        'priceListName' => 'D',
                    ],
                ]
            ],
            [
                'sorter' => ['priceListName' => ['data_name' => 'priceListName']],
                'defaultSorting' => ['priceListName' => 'DESC'],
                'expectedData' => [
                    [
                        'priceListId' => 41,
                        'priceListName' => 'D',
                    ],
                    [
                        'priceListId' => 34,
                        'priceListName' => 'C',
                    ],
                    [
                        'priceListId' => 5,
                        'priceListName' => 'B',
                    ],
                    [
                        'priceListId' => 256,
                        'priceListName' => 'A',
                    ]
                ]
            ],
            [
                'sorter' => ['priceListId' => ['data_name' => 'priceListId']],
                'defaultSorting' => ['priceListId' => 'ASC'],
                'expectedData' => [
                    [
                        'priceListId' => 5,
                        'priceListName' => 'B',
                    ],
                    [
                        'priceListId' => 34,
                        'priceListName' => 'C',
                    ],
                    [
                        'priceListId' => 41,
                        'priceListName' => 'D',
                    ],
                    [
                        'priceListId' => 256,
                        'priceListName' => 'A',
                    ]
                ]
            ],
            [
                'sorter' => ['priceListId' => ['data_name' => 'priceListId']],
                'defaultSorting' => ['priceListId' => 'DESC'],
                'expectedData' => [
                    [
                        'priceListId' => 256,
                        'priceListName' => 'A',
                    ],
                    [
                        'priceListId' => 41,
                        'priceListName' => 'D',
                    ],
                    [
                        'priceListId' => 34,
                        'priceListName' => 'C',
                    ],
                    [
                        'priceListId' => 5,
                        'priceListName' => 'B',
                    ],
                ],
            ],
        ];
    }
}
