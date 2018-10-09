<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\State;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;
use Oro\Bundle\DataGridBundle\Provider\State\SortersStateProvider;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class SortersStateProviderTest extends AbstractStateProviderTest
{
    /** @var SortersStateProvider */
    private $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->provider = new SortersStateProvider(
            $this->gridViewManager,
            $this->tokenAccessor,
            $this->datagridParametersHelper
        );
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenParameters(array $state, array $sortersColumns, array $expectedState): void
    {
        $this->mockParametersState($state, []);

        $this->mockSortersColumns($sortersColumns);

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
            ->with($this->datagridParameters, AbstractSorterExtension::SORTERS_ROOT_PARAM)
            ->willReturn($state);

        $this->datagridParametersHelper
            ->expects(self::exactly(1 - (int)$state))
            ->method('getFromMinifiedParameters')
            ->with($this->datagridParameters, AbstractSorterExtension::MINIFIED_SORTERS_PARAM)
            ->willReturn($minifiedState);
    }

    /**
     * @param array $sortersColumns
     */
    private function mockSortersColumns(array $sortersColumns): void
    {
        $this->datagridConfiguration
            ->expects(self::once())
            ->method('offsetGetByPath')
            ->with(SorterConfiguration::COLUMNS_PATH)
            ->willReturn($sortersColumns);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenMinifiedParameters(array $state, array $sortersColumns, array $expectedState): void
    {
        $this->mockParametersState([], $state);

        $this->mockSortersColumns($sortersColumns);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @return array
     */
    public function stateDataProvider(): array
    {
        return [
            'ensure state is normalized' => [
                'state' => [
                    'sampleColumn1' => AbstractSorterExtension::DIRECTION_DESC,
                    'sampleColumn2' => 1,
                    'sampleColumn3' => false,
                    'sampleColumn4' => AbstractSorterExtension::DIRECTION_ASC,
                    'sampleColumn5' => -1,
                    'sampleColumn6' => 'invalid_value',
                ],
                'sortersColumns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                    'sampleColumn3' => [],
                    'sampleColumn4' => [],
                    'sampleColumn5' => [],
                    'sampleColumn6' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => AbstractSorterExtension::DIRECTION_DESC,
                    'sampleColumn2' => AbstractSorterExtension::DIRECTION_DESC,
                    'sampleColumn3' => AbstractSorterExtension::DIRECTION_DESC,
                    'sampleColumn4' => AbstractSorterExtension::DIRECTION_ASC,
                    'sampleColumn5' => AbstractSorterExtension::DIRECTION_ASC,
                    'sampleColumn6' => AbstractSorterExtension::DIRECTION_ASC,
                ],
            ],
            'ensure state contains only defined columns' => [
                'state' => [
                    'sampleColumn1' => AbstractSorterExtension::DIRECTION_DESC,
                    'extraColumn' => 10,
                ],
                'sortersColumns' => [
                    'sampleColumn1' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => AbstractSorterExtension::DIRECTION_DESC,
                ],
            ],
            'ensure state contains only enabled columns' => [
                'state' => [
                    'sampleColumn1' => AbstractSorterExtension::DIRECTION_DESC,
                    'sampleColumn2' => 1,
                ],
                'sortersColumns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [PropertyInterface::DISABLED_KEY => true],
                ],
                'expectedState' => [
                    'sampleColumn1' => AbstractSorterExtension::DIRECTION_DESC,
                ],
            ],
        ];
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenCurrentGridView(array $state, array $sortersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->mockSortersColumns($sortersColumns);

        $this->mockGridName($gridName = 'sample-datagrid');
        $this->mockCurrentGridViewId($viewId = 'sample-view');

        $this->gridViewManager
            ->expects(self::once())
            ->method('getView')
            ->with($viewId, 1, $gridName)
            ->willReturn($gridView = $this->mockGridView('getSortersData', $state));

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }


    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenDefaultGridView(array $state, array $sortersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->mockSortersColumns($sortersColumns);

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
            ->willReturn($gridView = $this->mockGridView('getSortersData', $state));

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenGridViewsDisabled(array $state, array $sortersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->assertGridViewsDisabled();

        $this->datagridConfiguration
            ->expects(self::any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                [SorterConfiguration::COLUMNS_PATH, [], $sortersColumns],
                [SorterConfiguration::DISABLE_DEFAULT_SORTING_PATH, false, false],
                [SorterConfiguration::DEFAULT_SORTERS_PATH, [], $state],
            ]);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetStateWhenDefaultSortersState(array $state, array $sortersColumns, array $expectedState): void
    {
        $this->mockParametersState([], []);

        $this->assertNoCurrentNoDefaultGridView();

        $this->datagridConfiguration
            ->expects(self::any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                [SorterConfiguration::COLUMNS_PATH, [], $sortersColumns],
                [SorterConfiguration::DISABLE_DEFAULT_SORTING_PATH, false, false],
                [SorterConfiguration::DEFAULT_SORTERS_PATH, [], $state],
            ]);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    public function testGetStateWhenDefaultSortingDisabled(): void
    {
        $this->mockParametersState([], []);

        $this->assertNoCurrentNoDefaultGridView();

        $this->datagridConfiguration
            ->expects(self::any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                [SorterConfiguration::COLUMNS_PATH, [], ['sampleColumn1' => []]],
                [SorterConfiguration::DISABLE_DEFAULT_SORTING_PATH, false, true],
            ]);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals([], $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetStateFromParameters(array $state, array $sortersColumns, array $expectedState): void
    {
        $this->mockParametersState($state, []);

        $this->mockSortersColumns($sortersColumns);

        $actualState = $this->provider->getStateFromParameters($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetStateFromParametersWhenDefaultSortersState(
        array $state,
        array $sortersColumns,
        array $expectedState
    ): void {
        $this->mockParametersState([], []);

        $this->datagridConfiguration
            ->expects(self::any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                [SorterConfiguration::COLUMNS_PATH, [], $sortersColumns],
                [SorterConfiguration::DISABLE_DEFAULT_SORTING_PATH, false, false],
                [SorterConfiguration::DEFAULT_SORTERS_PATH, [], $state],
            ]);

        $actualState = $this->provider->getStateFromParameters($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $defaultSorters
     * @param array $sortersColumns
     * @param array $expectedState
     */
    public function testGetDefaultState(array $defaultSorters, array $sortersColumns, array $expectedState): void
    {
        $this->datagridConfiguration
            ->expects(self::any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                [SorterConfiguration::COLUMNS_PATH, [], $sortersColumns],
                [SorterConfiguration::DISABLE_DEFAULT_SORTING_PATH, false, false],
                [SorterConfiguration::DEFAULT_SORTERS_PATH, [], $defaultSorters],
            ]);

        $actualState = $this->provider->getDefaultState($this->datagridConfiguration);

        self::assertEquals($expectedState, $actualState);
    }

    public function testGetDefaultStateWhenDefaultSortingDisabled(): void
    {
        $this->datagridConfiguration
            ->expects(self::any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                [SorterConfiguration::COLUMNS_PATH, [], []],
                [SorterConfiguration::DISABLE_DEFAULT_SORTING_PATH, false, true],
            ]);

        $actualState = $this->provider->getDefaultState($this->datagridConfiguration);

        self::assertEquals([], $actualState);
    }
}
