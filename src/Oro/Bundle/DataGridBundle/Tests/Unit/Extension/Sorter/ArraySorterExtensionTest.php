<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\DataGridBundle\Extension\Sorter\ArraySorterExtension;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration;
use PHPUnit\Framework\MockObject\MockObject;

class ArraySorterExtensionTest extends AbstractSorterExtensionTestCase
{
    private DatagridConfiguration&MockObject $config;
    private ArrayDatasource $arrayDatasource;

    private array $arraySource = [
        [
            'priceListId'   => 256,
            'priceListName' => 'A',
        ],
        [
            'priceListId'   => 5,
            'priceListName' => 'B',
        ],
        [
            'priceListId'   => 34,
            'priceListName' => 'C',
        ],
        [
            'priceListId'   => 41,
            'priceListName' => 'D',
        ],
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(DatagridConfiguration::class);
        $this->arrayDatasource = new ArrayDatasource();
        $this->arrayDatasource->setArraySource($this->arraySource);
        $this->extension = new ArraySorterExtension($this->sortersStateProvider, $this->resolver);
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicableWithArrayDatasource(): void
    {
        $this->config->expects(self::once())
            ->method('getDatasourceType')
            ->willReturn(ArrayDatasource::TYPE);

        $this->config->expects(self::once())
            ->method('offsetGetByPath')
            ->with(Configuration::COLUMNS_PATH)->willReturn([]);

        self::assertTrue($this->extension->isApplicable($this->config));
    }

    public function testIsApplicableWithWrongDatasource(): void
    {
        $this->config->expects(self::once())
            ->method('getDatasourceType')
            ->willReturn(OrmDatasource::TYPE);

        $this->config->expects(self::never())
            ->method('offsetGetByPath')
            ->with(Configuration::COLUMNS_PATH)
            ->willReturn([]);

        self::assertFalse($this->extension->isApplicable($this->config));
    }

    /**
     * @dataProvider sortingDataProvider
     */
    public function testVisitDatasource(array $sorter, array $state, array $expectedData): void
    {
        $this->configureResolver();
        $this->config->expects(self::once())
            ->method('offsetGetByPath')
            ->with(Configuration::COLUMNS_PATH)
            ->willReturn($sorter);

        $this->sortersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->willReturn($state);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource($this->config, $this->arrayDatasource);

        self::assertEquals($expectedData, $this->arrayDatasource->getArraySource());
    }

    public function testVisitDatasourceWithWrongDatasourceType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->configureResolver();
        $this->config->expects(self::once())
            ->method('offsetGetByPath')
            ->with(Configuration::COLUMNS_PATH)
            ->willReturn(['priceListName' => ['data_name' => 'priceListName']]);

        $this->sortersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->willReturn(['priceListName' => 'DESC']);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            $this->config,
            $this->createMock(OrmDatasource::class)
        );
    }

    public function sortingDataProvider(): array
    {
        return [
            [
                'sorter'       => ['priceListName' => ['data_name' => 'priceListName']],
                'state'        => ['priceListName' => 'ASC'],
                'expectedData' => [
                    [
                        'priceListId'   => 256,
                        'priceListName' => 'A',
                    ],
                    [
                        'priceListId'   => 5,
                        'priceListName' => 'B',
                    ],
                    [
                        'priceListId'   => 34,
                        'priceListName' => 'C',
                    ],
                    [
                        'priceListId'   => 41,
                        'priceListName' => 'D',
                    ],
                ]
            ],
            [
                'sorter'       => ['priceListName' => ['data_name' => 'priceListName']],
                'state'        => ['priceListName' => 'DESC'],
                'expectedData' => [
                    [
                        'priceListId'   => 41,
                        'priceListName' => 'D',
                    ],
                    [
                        'priceListId'   => 34,
                        'priceListName' => 'C',
                    ],
                    [
                        'priceListId'   => 5,
                        'priceListName' => 'B',
                    ],
                    [
                        'priceListId'   => 256,
                        'priceListName' => 'A',
                    ]
                ]
            ],
            [
                'sorter'       => ['priceListId' => ['data_name' => 'priceListId']],
                'state'        => ['priceListId' => 'ASC'],
                'expectedData' => [
                    [
                        'priceListId'   => 5,
                        'priceListName' => 'B',
                    ],
                    [
                        'priceListId'   => 34,
                        'priceListName' => 'C',
                    ],
                    [
                        'priceListId'   => 41,
                        'priceListName' => 'D',
                    ],
                    [
                        'priceListId'   => 256,
                        'priceListName' => 'A',
                    ]
                ]
            ],
            [
                'sorter'       => ['priceListId' => ['data_name' => 'priceListId']],
                'state'        => ['priceListId' => 'DESC'],
                'expectedData' => [
                    [
                        'priceListId'   => 256,
                        'priceListName' => 'A',
                    ],
                    [
                        'priceListId'   => 41,
                        'priceListName' => 'D',
                    ],
                    [
                        'priceListId'   => 34,
                        'priceListName' => 'C',
                    ],
                    [
                        'priceListId'   => 5,
                        'priceListName' => 'B',
                    ],
                ],
            ],
        ];
    }
}
