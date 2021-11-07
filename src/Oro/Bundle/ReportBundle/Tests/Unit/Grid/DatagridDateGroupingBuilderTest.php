<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Tools\DateHelper;
use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Exception\InvalidDatagridConfigException;
use Oro\Bundle\ReportBundle\Grid\DatagridDateGroupingBuilder;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings("PMD.ExcessiveMethodLength")
 */
class DatagridDateGroupingBuilderTest extends \PHPUnit\Framework\TestCase
{
    private string $calendarDateEntity = CalendarDate::class;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var JoinIdentifierHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $joinIdHelper;

    /** @var DateHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $dateHelper;

    /** @var DatagridDateGroupingBuilder */
    private $datagridDateGroupingBuilder;

    protected function setUp(): void
    {
        $this->config = DatagridConfiguration::create([]);
        $this->joinIdHelper = $this->createMock(JoinIdentifierHelper::class);
        $this->dateHelper = $this->createMock(DateHelper::class);

        $this->datagridDateGroupingBuilder = new DatagridDateGroupingBuilder(
            $this->calendarDateEntity,
            $this->dateHelper,
            $this->joinIdHelper
        );
    }

    public function testIgnoreIfGroupingNotRequired()
    {
        $report = new Report();
        $this->joinIdHelper->expects($this->never())
            ->method('getFieldName');

        $this->datagridDateGroupingBuilder->applyDateGroupingFilterIfRequired($this->config, $report);

        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([
            DateGroupingType::DATE_GROUPING_NAME => []
        ]));
        $this->datagridDateGroupingBuilder->applyDateGroupingFilterIfRequired($this->config, $report);

        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([
            DateGroupingType::DATE_GROUPING_NAME => [DateGroupingType::FIELD_NAME_ID => 'ddd']
        ]));
        $this->datagridDateGroupingBuilder->applyDateGroupingFilterIfRequired($this->config, $report);
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([
            DateGroupingType::DATE_GROUPING_NAME => [DateGroupingType::FIELD_NAME_ID => 'ddd']
        ]));
        $this->datagridDateGroupingBuilder->applyDateGroupingFilterIfRequired($this->config, $report);
    }

    /**
     * @dataProvider exceptionTestProvider
     */
    public function testThrowsIfInvalidGrid(array $params)
    {
        $this->expectException(InvalidDatagridConfigException::class);
        $this->joinIdHelper->expects($this->never())
            ->method('getFieldName');
        $this->config->merge($params);
        $this->datagridDateGroupingBuilder->applyDateGroupingFilterIfRequired(
            $this->config,
            $this->getPreconfiguredReport()
        );
    }

    public function exceptionTestProvider(): array
    {
        return [
            [
                [
                    DatagridDateGroupingBuilder::SOURCE_KEY_NAME => [],
                ],
            ],
            [
                [
                    DatagridDateGroupingBuilder::SOURCE_KEY_NAME => ['query_config' => []],
                ],
            ],
            [
                [
                    DatagridDateGroupingBuilder::SOURCE_KEY_NAME => ['query_config' => ['column_aliases']],
                ],
            ],
        ];
    }

    public function testConfigPassesButTableAliasThrows()
    {
        $this->joinIdHelper->expects($this->once())
            ->method('getFieldName');
        $this->joinIdHelper->expects($this->once())
            ->method('explodeColumnName')
            ->willReturn(['noneValidValue']);
        $this->expectException(InvalidDatagridConfigException::class);
        $this->config->merge($this->getPreconfiguredConfig());
        $this->datagridDateGroupingBuilder->applyDateGroupingFilterIfRequired(
            $this->config,
            $this->getPreconfiguredReport()
        );
    }

    /**
     * @dataProvider validConfigurationProvider
     */
    public function testValidDateGroupingConfiguration(Report $report, array $inputConfig, array $expectedConfig)
    {
        $this->joinIdHelper->expects($this->once())
            ->method('getFieldName')
            ->willReturn('createdAt');

        $this->joinIdHelper->expects($this->once())
            ->method('explodeColumnName')
            ->willReturn(['']);

        $this->config->merge($inputConfig);

        $this->dateHelper->expects($this->once())
            ->method('getConvertTimezoneExpression')
            ->with('t1.createdAt')
            ->willReturn("CONVERT_TZ(t1.createdAt, '+00:00', '+10:00')");

        $this->datagridDateGroupingBuilder->applyDateGroupingFilterIfRequired(
            $this->config,
            $report
        );

        $this->assertEquals($expectedConfig, $this->config->toArray());
    }

    public function validConfigurationProvider(): array
    {
        $originalConfig3 = $this->getPreconfiguredConfig();
        $originalConfig3['source']['query']['join']['left'][0] = [
            'join' => Group::class,
            'alias' => 't2',
        ];
        $expectedException3 = $this->getExpectedConfig(true);
        $expectedException3['source']['query']['join']['left'][1] =
            $originalConfig3['source']['query']['join']['left'][0];

        return [
            [
                $this->getPreconfiguredReport(),
                $this->getPreconfiguredConfig(),
                $this->getExpectedConfig(true)
            ],
            [
                $this->getPreconfiguredReport('createdAt', false),
                $this->getPreconfiguredConfig(),
                $this->getExpectedConfig(false),
            ],
            [
                $this->getPreconfiguredReport(),
                $originalConfig3,
                $expectedException3,
            ],
        ];
    }

    private function getPreconfiguredReport(string $fieldName = 'createdAt', bool $useSkipEmptyPeriods = true): Report
    {
        $report = new Report();
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([
            DateGroupingType::DATE_GROUPING_NAME => [
                DateGroupingType::FIELD_NAME_ID => $fieldName,
                DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID => $useSkipEmptyPeriods,
            ]
        ]));

        return $report;
    }

    private function getPreconfiguredConfig(): array
    {
        return [
            'columns' => [
                'c1' => [
                    'frontend_type' => 'datetime',
                    'label' => 'Created At',
                    'translatable' => false,
                ],
                'c2' => [
                    'frontend_type' => 'integer',
                    'label' => 'Id',
                    'translatable' => false,
                ],
            ],
            'fields_acl' => [
                'columns' => [
                    'c1' => ['data_name' => 't1.createdAt'],
                    'c2' => ['data_name' => 't1.id'],
                ],
            ],
            'filters' => [
                'columns' => [
                    'c1' => [
                        'data_name' => 'c1',
                        'translatable' => false,
                        'type' => 'datetime',
                    ],
                    'c2' => [
                        'data_name' => 'c2',
                        'options' => ['data_type' => 'data_integer'],
                        'translatable' => false,
                        'type' => 'number-range',
                    ],
                ],
            ],

            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                ],
            ],
            'source' => [
                'query' => [
                    'from' => [
                        0 => [
                            'alias' => 't1',
                            'table' => User::class,
                        ],
                    ],
                    'select' => [
                        't1.createdAt as c1',
                        't1.id as c2',
                        't1.id',
                    ],
                    'groupBy' => 'c2',
                ],
                'query_config' => [
                    'column_aliases' => [
                        'createdAt' => 'c1',
                        'id' => 'c2',
                    ],
                    'table_aliases' => ['' => 't1'],
                ],
            ],
            'properties' => [
                'id' => null,
                'view_link' => []
            ]
        ];
    }

    private function getExpectedConfig(bool $includeEmptyPeriods = false): array
    {
        $additional = [
            'filters' => [
                'columns' => [
                    'date_grouping' => [
                        'type' => 'date_grouping',
                        'data_name' => 'calendarDate.date',
                        'label' => 'oro.report.filter.grouping.label',
                        'column_name' => 'timePeriod',
                        'calendar_entity' => $this->calendarDateEntity,
                        'target_entity' => null,
                        'not_nullable_field' => 't1.id',
                        'joined_column' => 'createdAt',
                        'joined_table' => 't1',
                        'options' => [
                            'field_options' => [
                                'choices' => [
                                    'Day' => 'day',
                                    'Month' => 'month',
                                    'Quarter' => 'quarter',
                                    'Year' => 'year',
                                ]
                            ],
                            'default_value' => 'Day'
                        ]
                    ],
                    'datePeriodFilter' => [
                        'label' => 'oro.report.datagrid.column.time_period.label',
                        'type' => 'datetime',
                        'data_name' => 't1.createdAt',
                    ],
                ],
                'default' => [
                    'date_grouping' => [
                        'value' => 'day',
                    ],
                ],
            ],
            'sorters' => [
                'columns' => [
                    'timePeriod' => [
                        'data_name' => 'timePeriod',
                        'apply_callback' => ['@oro_filter.date_grouping_filter', 'applyOrderBy']
                    ],
                ],
                'default' => [
                    'timePeriod' => 'DESC',
                ],
            ],
            'source' => [
                'query' => [
                    'select' => [],
                    'join' => [
                        'left' => [
                            0 => [
                                'join' => User::class,
                                'alias' => 't1',
                                'conditionType' => 'WITH',
                                'condition' =>
                                    'CAST(calendarDate.date as DATE) = '
                                    . "CAST(CONVERT_TZ(t1.createdAt, '+00:00', '+10:00') as DATE)",
                            ],
                        ],
                    ],
                ],
            ]
        ];

        if ($includeEmptyPeriods) {
            $skipEmptyPeriodsConfig = [
                'filters' => [
                    'columns' => [
                        'skip_empty_periods' => [
                            'type' => 'skip_empty_periods',
                            'data_name' => 't1.id',
                            'label' => 'oro.report.filter.skip_empty_periods.label',
                            'options' => [
                                'field_options' => [
                                    'choices' => [
                                        'No' => 0,
                                        'Yes' => 1,
                                    ]
                                ],
                                'default_value' => 'Yes'
                            ]
                        ],
                    ],
                    'default' => [
                        'skip_empty_periods' => [
                            'value' => 1,
                        ],
                    ],
                ],
            ];
            $additional = array_merge_recursive($additional, $skipEmptyPeriodsConfig);
        }

        $originalConfig = $this->getPreconfiguredConfig();
        $columns = [
            'cDate' => [
                'frontend_type' => 'date',
                'renderable' => false,
            ],
            'timePeriod' => [
                'label' => 'oro.report.datagrid.column.time_period.label',
            ],
        ];
        $columns += $originalConfig['columns'];
        $originalConfig['columns'] = $columns;
        $originalConfig['source']['query']['from'][0] = [
            'alias' => 'calendarDate',
            'table' => $this->calendarDateEntity,
        ];
        $merged = array_merge_recursive($originalConfig, $additional);
        $merged['source']['query']['groupBy'] = 'c2';
        $merged['properties'] = [];

        return $merged;
    }
}
