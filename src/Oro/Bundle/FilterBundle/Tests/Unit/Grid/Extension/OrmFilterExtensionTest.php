<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid\Extension;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

class OrmFilterExtensionTest extends AbstractFilterExtensionTestCase
{
    /** @var OrmFilterExtension */
    protected $extension;

    /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject */
    private $datasource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new OrmFilterExtension(
            $this->configurationProvider,
            $this->filterBag,
            $this->filtersProvider,
            $this->filtersMetadataProvider,
            $this->filtersStateProvider,
            new FilterExecutionContext()
        );

        $this->datasource = $this->createMock(OrmDatasource::class);
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(array $datagridConfigArray, bool $expectedResult): void
    {
        $datagridConfig = $this->createDatagridConfig($datagridConfigArray);

        $this->extension->setParameters($this->datagridParameters);

        self::assertSame($expectedResult, $this->extension->isApplicable($datagridConfig));
    }

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
        $datagridConfig = $this->createDatagridConfig(['name' => self::DATAGRID_NAME]);

        $this->mockFiltersState([]);
        $this->mockDatasource($this->createMock(QueryBuilder::class), null);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }

    private function mockFiltersState(array $filtersState): void
    {
        $this->filtersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->with(self::isInstanceOf(DatagridConfiguration::class), $this->datagridParameters)
            ->willReturn($filtersState);
    }

    private function mockDatasource(QueryBuilder $queryBuilder, ?QueryBuilder $countQueryBuilder): void
    {
        $this->datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->datasource->expects(self::once())
            ->method('getCountQb')
            ->willReturn($countQueryBuilder);
    }

    public function testVisitDataSourceWhenNoState(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState([]);
        $this->mockDatasource($this->createMock(QueryBuilder::class), null);

        $filter->expects(self::never())
            ->method('apply');

        $this->filterBag->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }

    public function testVisitDataSourceWhenFilterStateNotValid(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState([self::FILTER_NAME => ['value' => 'sampleFilterValue1']]);
        $this->mockDatasource($this->createMock(QueryBuilder::class), null);

        $filterForm = $this->mockFilterForm($filter);
        $filterForm->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $filter->expects(self::never())
            ->method('apply');

        $this->filterBag->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }

    public function testVisitDataSourceWhenNoCountQueryBuilder(): void
    {
        $filtersState = [self::FILTER_NAME => ['value' => 'sampleFilterValue1']];
        $formData = ['value' => 'sampleFilterValue1'];

        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState($filtersState);
        $this->mockDatasource($this->createMock(QueryBuilder::class), null);

        $filterForm = $this->mockFilterForm($filter);
        $filterForm->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $filterForm->expects(self::once())
            ->method('getData')
            ->willReturn($formData);

        $filter->expects(self::once())
            ->method('apply')
            ->with(self::isInstanceOf(OrmFilterDatasourceAdapter::class), $formData)
            ->willReturn(true);

        $this->filterBag->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }

    public function testVisitDataSourceWhenHasCountQueryBuilder(): void
    {
        $filtersState = [self::FILTER_NAME => ['value' => 'sampleFilterValue1']];
        $formData = ['value' => 'sampleFilterValue1'];

        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState($filtersState);
        $this->mockDatasource($this->createMock(QueryBuilder::class), $this->createMock(QueryBuilder::class));

        $filterForm = $this->mockFilterForm($filter);
        $filterForm->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $filterForm->expects(self::once())
            ->method('getData')
            ->willReturn($formData);

        $filter->expects(self::exactly(2))
            ->method('apply')
            ->withConsecutive(
                [self::isInstanceOf(OrmFilterDatasourceAdapter::class), $formData],
                [self::isInstanceOf(OrmFilterDatasourceAdapter::class), $formData]
            )
            ->willReturn(true);

        $this->filterBag->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }
}
