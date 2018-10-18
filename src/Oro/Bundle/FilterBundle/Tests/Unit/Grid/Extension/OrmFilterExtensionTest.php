<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid\Extension;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

class OrmFilterExtensionTest extends AbstractFilterExtensionTestCase
{
    /** @var OrmFilterExtension */
    protected $extension;

    /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject */
    private $datasource;

    protected function setUp()
    {
        parent::setUp();

        $this->extension = new OrmFilterExtension(
            $this->configurationProvider,
            $this->filtersStateProvider,
            $this->translator
        );

        $this->datasource = $this->createMock(OrmDatasource::class);
    }

    /**
     * @dataProvider isApplicableDataProvider
     *
     * @param array $datagridConfigArray
     * @param bool $expectedResult
     */
    public function testIsApplicable(array $datagridConfigArray, bool $expectedResult): void
    {
        $datagridConfig = $this->createDatagridConfig($datagridConfigArray);

        $this->extension->setParameters($this->datagridParameters);

        self::assertSame($expectedResult, $this->extension->isApplicable($datagridConfig));
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider(): array
    {
        return [
            'applicable' => [
                'datagridConfigArray' => [
                    'source' => ['type' => OrmDatasource::TYPE],
                    'filters' => ['columns' => []],
                ],
                'expectedResult' => true,
            ],
            'unsupported source type' => [
                'datagridConfigArray' => [
                    'source' => ['type' => 'sampleType'],
                    'filters' => ['columns' => []],
                ],
                'expectedResult' => false,
            ],
            'no columns' => [
                'datagridConfigArray' => [
                    'source' => ['type' => 'sampleType'],
                    'filters' => [],
                ],
                'expectedResult' => false,
            ],
            'empty config array' => [
                'datagridConfigArray' => [],
                'expectedResult' => false,
            ],
        ];
    }

    public function testVisitDataSourceWhenNoFilters(): void
    {
        $datagridConfig = $this->createDatagridConfig(['name' => static::DATAGRID_NAME]);

        $this->mockFiltersState([]);
        $this->mockDatasource($this->createMock(QueryBuilder::class), null);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }

    /**
     * @param array $filtersState
     */
    private function mockFiltersState(array $filtersState): void
    {
        $this->filtersStateProvider
            ->expects(self::once())
            ->method('getStateFromParameters')
            ->with(self::isInstanceOf(DatagridConfiguration::class), $this->datagridParameters)
            ->willReturn($filtersState);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param QueryBuilder|null $countQueryBuilder
     */
    private function mockDatasource(QueryBuilder $queryBuilder, ?QueryBuilder $countQueryBuilder): void
    {
        $this->datasource
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->datasource
            ->expects(self::once())
            ->method('getCountQb')
            ->willReturn($countQueryBuilder);
    }

    public function testVisitDataSourceWhenNoState(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState([]);
        $this->mockDatasource($this->createMock(QueryBuilder::class), null);

        $filter
            ->expects(self::never())
            ->method('apply');

        $this->extension->addFilter(static::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }

    public function testVisitDataSourceWhenFilterStateNotValid(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState([static::FILTER_NAME => ['value' => 'sampleFilterValue1']]);
        $this->mockDatasource($this->createMock(QueryBuilder::class), null);

        $filterForm = $this->mockFilterForm($filter);

        $filterForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $filter
            ->expects(self::never())
            ->method('apply');

        $this->extension->addFilter(static::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }

    /**
     * @dataProvider visitDataSourceDataProvider
     *
     * @param array $filtersState
     * @param array $formData
     * @param array $expectedFormData
     */
    public function testVisitDataSourceWhenNoCountQueryBuilder(
        array $filtersState,
        array $formData,
        array $expectedFormData
    ): void {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState($filtersState);
        $this->mockDatasource($this->createMock(QueryBuilder::class), null);

        $filterForm = $this->mockFilterForm($filter);

        $filterForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $filterForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($formData);

        $filter
            ->expects(self::once())
            ->method('apply')
            ->with(self::isInstanceOf(OrmFilterDatasourceAdapter::class), $expectedFormData);

        $this->extension->addFilter(static::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }

    /**
     * @return array
     */
    public function visitDataSourceDataProvider(): array
    {
        return [
            'regular filter' => [
                'filtersState' => [static::FILTER_NAME => ['value' => 'sampleFilterValue1']],
                'formData' => ['value' => 'sampleFilterValue1'],
                'expectedFormData' => ['value' => 'sampleFilterValue1'],
            ],
            'has date interval start, no date interval end' => [
                'filtersState' => [static::FILTER_NAME => ['value' => ['start' => 'sampleValue1']]],
                'formData' => ['value' => ['start' => 'sampleValueSubmitted1']],
                'expectedFormData' => [
                    'value' => [
                        'start' => 'sampleValueSubmitted1',
                        'start_original' => 'sampleValue1',
                    ],
                ],
            ],
            'no date interval start, has date interval end' => [
                'filtersState' => [static::FILTER_NAME => ['value' => ['end' => 'sampleValue1']]],
                'formData' => ['value' => ['end' => 'sampleValueSubmitted1']],
                'expectedFormData' => [
                    'value' => [
                        'end' => 'sampleValueSubmitted1',
                        'end_original' => 'sampleValue1',
                    ],
                ],
            ],
            'has date interval start, has date interval end' => [
                'filtersState' => [
                    static::FILTER_NAME => [
                        'value' => [
                            'start' => 'sampleValue1',
                            'end' => 'sampleValue2',
                        ],
                    ],
                ],
                'formData' => ['value' => ['start' => 'sampleValueSubmitted1', 'end' => 'sampleValueSubmitted2']],
                'expectedFormData' => [
                    'value' => [
                        'start' => 'sampleValueSubmitted1',
                        'start_original' => 'sampleValue1',
                        'end' => 'sampleValueSubmitted2',
                        'end_original' => 'sampleValue2',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider visitDataSourceDataProvider
     *
     * @param array $filtersState
     * @param array $formData
     * @param array $expectedFormData
     */
    public function testVisitDataSourceWhenHasCountQueryBuilder(
        array $filtersState,
        array $formData,
        array $expectedFormData
    ): void {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState($filtersState);
        $this->mockDatasource($this->createMock(QueryBuilder::class), $this->createMock(QueryBuilder::class));

        $filterForm = $this->mockFilterForm($filter);

        $filterForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $filterForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($formData);

        $filter
            ->expects(self::exactly(2))
            ->method('apply')
            ->withConsecutive(
                [self::isInstanceOf(OrmFilterDatasourceAdapter::class), $expectedFormData],
                [self::isInstanceOf(OrmFilterDatasourceAdapter::class), $expectedFormData]
            );

        $this->extension->addFilter(static::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }
}
