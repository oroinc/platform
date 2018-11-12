<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\Filter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Grid\Extension\AbstractFilterExtensionTestCase;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Extension\Filter\SearchFilterExtension;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchFilterExtensionTest extends AbstractFilterExtensionTestCase
{
    /** @var OrmFilterExtension */
    protected $extension;

    /** @var SearchDatasource|\PHPUnit\Framework\MockObject\MockObject */
    private $datasource;

    protected function setUp()
    {
        parent::setUp();

        $this->extension = new SearchFilterExtension(
            $this->configurationProvider,
            $this->filtersStateProvider,
            $this->translator
        );

        $this->datasource = $this->createMock(SearchDatasource::class);
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
                    'source' => ['type' => SearchDatasource::TYPE],
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
        $this->mockDatasource();

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

    private function mockDatasource(): void
    {
        $this->datasource
            ->expects(self::once())
            ->method('getSearchQuery')
            ->willReturn($this->createMock(SearchQueryInterface::class));
    }

    public function testVisitDataSourceWhenNoState(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState([]);
        $this->mockDatasource();

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
        $this->mockDatasource();

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

    public function testVisitDataSource(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->mockFiltersState([static::FILTER_NAME => ['value' => 'sampleFilterValue1']]);
        $this->mockDatasource();

        $filterForm = $this->mockFilterForm($filter);

        $filterForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $filterForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($formData = ['value' => 'SampleFilterValueSubmitted1']);

        $filter
            ->expects(self::once())
            ->method('apply')
            ->with(self::isInstanceOf(SearchFilterDatasourceAdapter::class), $formData);

        $this->extension->addFilter(static::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitDatasource($datagridConfig, $this->datasource);
    }
}
