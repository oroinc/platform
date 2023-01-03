<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Provider\State;

use Oro\Bundle\DataGridBundle\Tests\Unit\Provider\State\AbstractStateProviderTest;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;
use Oro\Bundle\FilterBundle\Provider\State\FiltersStateProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class FiltersStateProviderTest extends AbstractStateProviderTest
{
    use EntityTrait;

    /** @var FiltersStateProvider */
    private $provider;

    private const DEFAULT_FILTERS_STATE = ['sampleFilter' => ['value' => 'sampleValue']];

    protected function setUp(): void
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
     */
    public function testGetStateWhenParameters(array $state, array $filtersColumns, array $expectedState): void
    {
        $this->mockParametersState($state, []);

        $this->mockFiltersColumns($filtersColumns, self::DEFAULT_FILTERS_STATE);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    private function mockParametersState(array $state, array $minifiedState): void
    {
        $this->datagridParametersHelper->expects(self::once())
            ->method('getFromParameters')
            ->with($this->datagridParameters, AbstractFilterExtension::FILTER_ROOT_PARAM)
            ->willReturn($state);

        $this->datagridParametersHelper->expects(self::once())
            ->method('getFromMinifiedParameters')
            ->with($this->datagridParameters, AbstractFilterExtension::MINIFIED_FILTER_PARAM)
            ->willReturn($minifiedState);
    }

    private function mockFiltersColumns(array $filtersColumns, array $defaultFilters): void
    {
        $this->datagridConfiguration->expects(self::exactly(2))
            ->method('offsetGetByPath')
            ->willReturnMap([
                [FilterConfiguration::COLUMNS_PATH, [], $filtersColumns],
                [FilterConfiguration::DEFAULT_FILTERS_PATH, [], $defaultFilters],
            ]);
    }

    /**
     * @dataProvider stateDataProvider
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
     */
    public function testGetStateWhenCurrentGridView(array $state, array $filtersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->mockFiltersColumns($filtersColumns, self::DEFAULT_FILTERS_STATE);

        $this->mockGridName($gridName = 'sample-datagrid');
        $this->mockCurrentGridViewId($viewId = 'sample-view');

        $this->gridViewManager->expects(self::once())
            ->method('getView')
            ->with($viewId, 1, $gridName)
            ->willReturn($this->mockGridView('getFiltersData', $state));

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     */
    public function testGetStateWhenDefaultGridView(array $state, array $filtersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->mockFiltersColumns($filtersColumns, self::DEFAULT_FILTERS_STATE);

        $this->mockGridName($gridName = 'sample-datagrid');

        $this->assertNoCurrentGridView();

        $user = $this->getEntity(User::class, ['id' => 42]);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->gridViewManager->expects(self::once())
            ->method('getDefaultView')
            ->with($user, $gridName)
            ->willReturn($this->mockGridView('getFiltersData', $state));

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
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
