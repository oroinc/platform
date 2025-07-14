<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ChartOptionsBuilder;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChartOptionsBuilderTest extends TestCase
{
    private EntityFieldProvider&MockObject $entityFieldProvider;
    private DateHelper&MockObject $dateHelper;
    private DatagridInterface&MockObject $datagrid;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityFieldProvider = $this->createMock(EntityFieldProvider::class);
        $this->dateHelper = $this->createMock(DateHelper::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);
    }

    private function assertBuilder(Report $report): ChartOptionsBuilder
    {
        return new ChartOptionsBuilder($this->entityFieldProvider, $this->dateHelper, $report, $this->datagrid);
    }

    private function assertReport(array $chartOptions): Report
    {
        $report = new Report();
        $report->setChartOptions($chartOptions);
        $report->setEntity(User::class);

        return $report;
    }

    /**
     * @dataProvider dataProvider
     */
    public function testBuildOptions(
        array $chartOptions,
        array $gridConfig,
        array $gridData,
        array $fields,
        array $expected
    ): void {
        $report = $this->assertReport($chartOptions);
        $builder = $this->assertBuilder($report);

        $this->datagrid->expects($this->any())
            ->method('getConfig')
            ->willReturn(DatagridConfiguration::create($gridConfig));
        $this->datagrid->expects($this->any())
            ->method('getData')
            ->willReturn(ResultsObject::create($gridData));

        $this->dateHelper->expects($this->once())
            ->method('getFormatStrings')
            ->willReturn(['viewType' => 'year']);

        $this->entityFieldProvider->expects($this->once())
            ->method('getEntityFields')
            ->with(User::class, EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS)
            ->willReturn($fields);

        $result = $builder->buildChartOptions();

        $this->assertEquals($expected, $result);
    }

    public function dataProvider(): array
    {
        return [
            'build' => [
                'chartOptions' => [
                    'data_schema' => [
                        'label' => 'CreatedAt'
                    ]
                ],
                'gridConfig' => [
                    'columns' => [
                        'c1' => [
                            'frontend_type' => 'date'
                        ]
                    ],
                    'source' => [
                        'query_config' => [
                            'column_aliases' => [
                                'CreatedAt' => 'c1'
                            ]
                        ]
                    ]
                ],
                'gridData' => [
                    'data' => [
                        ['c1' => '2021-01-01 23:00:00'],
                        ['c1' => '2022-01-01 23:00:00']
                    ]
                ],
                'fields' => [
                    ['name' => 'id', 'type' => 'integer'],
                    ['name' => 'title', 'type' => 'string'],
                ],
                'expected' => [
                    'data_schema' => [
                        'label' => [
                            'field_name' => 'c1',
                            'type' => 'year'
                        ]
                    ],
                    'field_types' => [
                        'id' => 'integer',
                        'title' => 'string'
                    ],
                    'original_data_schema' => [
                        'c1' => 'CreatedAt'
                    ]
                ],
            ],
            'not aliases' => [
                'chartOptions' => [
                    'data_schema' => [
                        'label' => 'CreatedAt'
                    ]
                ],
                'gridConfig' => [
                    'columns' => [
                        'CreatedAt' => [
                            'frontend_type' => 'date'
                        ]
                    ],
                ],
                'gridData' => [
                    'data' => [
                        ['CreatedAt' => '2021-01-01 23:00:00'],
                        ['CreatedAt' => '2022-01-01 23:00:00']
                    ]
                ],
                'fields' => [
                    ['name' => 'id', 'type' => 'integer'],
                    ['name' => 'title', 'type' => 'string'],
                ],
                'expected' => [
                    'data_schema' => [
                        'label' => [
                            'field_name' => 'CreatedAt',
                            'type' => 'year'
                        ]
                    ],
                    'field_types' => [
                        'id' => 'integer',
                        'title' => 'string'
                    ]
                ],
            ]
        ];
    }
}
