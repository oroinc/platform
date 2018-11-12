<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Provider\State;

use Oro\Bundle\DataGridBundle\Tests\Unit\Provider\State\AbstractStateProviderTest;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;
use Oro\Bundle\FilterBundle\Provider\State\FiltersStateProvider;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class FiltersStateProviderTest extends AbstractStateProviderTest
{
    /** @var FiltersStateProvider */
    private $provider;

    private const DEFAULT_FILTERS_STATE = ['sampleFilter' => ['value' => 'sampleValue']];

    protected function setUp()
    {
        parent::setUp();

        $this->provider = new FiltersStateProvider(
            $this->gridViewManager,
            $this->tokenAccessor,
            $this->datagridParametersHelper
        );
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $filtersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenParameters(array $state, array $filtersColumns, array $expectedState): void
    {
        $this->mockParametersState($state, []);

        $this->mockFiltersColumns($filtersColumns, self::DEFAULT_FILTERS_STATE);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @param array $state
     * @param array $minifiedState
     */
    private function mockParametersState(array $state, array $minifiedState): void
    {
        $this->datagridParametersHelper
            ->expects(self::once())
            ->method('getFromParameters')
            ->with($this->datagridParameters, AbstractFilterExtension::FILTER_ROOT_PARAM)
            ->willReturn($state);

        $this->datagridParametersHelper
            ->expects(self::once())
            ->method('getFromMinifiedParameters')
            ->with($this->datagridParameters, AbstractFilterExtension::MINIFIED_FILTER_PARAM)
            ->willReturn($minifiedState);
    }

    /**
     * @param array $filtersColumns
     * @param array $defaultFilters
     */
    private function mockFiltersColumns(array $filtersColumns, array $defaultFilters): void
    {
        $this->datagridConfiguration
            ->expects(self::exactly(2))
            ->method('offsetGetByPath')
            ->willReturnMap([
                [FilterConfiguration::COLUMNS_PATH, [], $filtersColumns],
                [FilterConfiguration::DEFAULT_FILTERS_PATH, [], $defaultFilters],
            ]);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $filtersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenMinifiedParameters(
        array $state,
        array $filtersColumns,
        array $expectedState
    ): void {
        $this->mockParametersState([], $state);

        $this->mockFiltersColumns($filtersColumns, self::DEFAULT_FILTERS_STATE);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @return array
     */
    public function stateDataProvider(): array
    {
        return [
            'ensure state contains only defined filters' => [
                'state' => [
                    'sampleFilter1' => ['value' => 'sampleValue1'],
                    'undefinedFilter1' => ['value' => 'sampleValue1'],
                ],
                'filtersColumns' => [
                    'sampleFilter1' => [],
                ],
                'expectedState' => [
                    'sampleFilter1' => ['value' => 'sampleValue1'],
                ],
            ],
            'ensure state can contain filters with alternate names' => [
                'state' => [
                    'sampleFilter1' => ['value' => 'sampleValue1'],
                    '__sampleFilter2' => ['value' => 'sampleValue2'],
                ],
                'filtersColumns' => [
                    'sampleFilter1' => [],
                    'sampleFilter2' => [],
                ],
                'expectedState' => [
                    'sampleFilter1' => ['value' => 'sampleValue1'],
                    '__sampleFilter2' => ['value' => 'sampleValue2'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $filtersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenCurrentGridView(array $state, array $filtersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->mockFiltersColumns($filtersColumns, self::DEFAULT_FILTERS_STATE);

        $this->mockGridName($gridName = 'sample-datagrid');
        $this->mockCurrentGridViewId($viewId = 'sample-view');

        $this->gridViewManager
            ->expects(self::once())
            ->method('getView')
            ->with($viewId, 1, $gridName)
            ->willReturn($gridView = $this->mockGridView('getFiltersData', $state));

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $filtersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenDefaultGridView(array $state, array $filtersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->mockFiltersColumns($filtersColumns, self::DEFAULT_FILTERS_STATE);

        $this->mockGridName($gridName = 'sample-datagrid');

        $this->assertNoCurrentGridView();

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user = $this->createMock(AbstractUser::class));

        $this->gridViewManager
            ->expects(self::once())
            ->method('getDefaultView')
            ->with($user, $gridName)
            ->willReturn($gridView = $this->mockGridView('getFiltersData', $state));

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $filtersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenGridViewsDisabled(array $state, array $filtersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->mockFiltersColumns($filtersColumns, $state);

        $this->assertGridViewsDisabled();

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $filtersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenDefaultFiltersState(array $state, array $filtersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->mockFiltersColumns($filtersColumns, $state);

        $this->assertNoCurrentNoDefaultGridView();

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $filtersColumns
     * @param array $expectedState
     */
    public function testGetStateFromParameters(
        array $state,
        array $filtersColumns,
        array $expectedState
    ): void {
        $this->mockParametersState($state, []);

        $this->mockFiltersColumns($filtersColumns, self::DEFAULT_FILTERS_STATE);

        $actualState = $this->provider->getStateFromParameters($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $defaultFilters
     * @param array $filtersColumns
     * @param array $expectedState
     */
    public function testGetDefaultState(
        array $defaultFilters,
        array $filtersColumns,
        array $expectedState
    ): void {
        $this->mockFiltersColumns($filtersColumns, $defaultFilters);

        $actualState = $this->provider->getDefaultState($this->datagridConfiguration);

        self::assertEquals($expectedState, $actualState);
    }
}
