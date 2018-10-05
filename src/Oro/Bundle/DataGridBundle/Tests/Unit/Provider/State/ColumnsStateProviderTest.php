<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\State;

use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class ColumnsStateProviderTest extends AbstractStateProviderTest
{
    /** @var ColumnsStateProvider */
    private $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->provider = new ColumnsStateProvider(
            $this->gridViewManager,
            $this->tokenAccessor,
            $this->datagridParametersHelper
        );
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $columns
     * @param array $expectedState
     */
    public function testGetStateWhenParameters(array $state, array $columns, array $expectedState): void
    {
        $this->mockParametersState($state, '');

        $this->mockColumns($columns);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @param array $state
     * @param string $minifiedState
     */
    private function mockParametersState(array $state, string $minifiedState): void
    {
        $this->datagridParametersHelper
            ->expects(self::once())
            ->method('getFromParameters')
            ->with($this->datagridParameters, ColumnsExtension::COLUMNS_PARAM)
            ->willReturn($state);

        $this->datagridParametersHelper
            ->expects(self::exactly(1 - (int)$state))
            ->method('getFromMinifiedParameters')
            ->with($this->datagridParameters, ColumnsExtension::MINIFIED_COLUMNS_PARAM)
            ->willReturn($minifiedState);
    }

    /**
     * @param array $columns
     */
    private function mockColumns(array $columns): void
    {
        $this->datagridConfiguration
            ->expects(self::once())
            ->method('offsetGet')
            ->with(Configuration::COLUMNS_KEY)
            ->willReturn($columns);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function stateDataProvider(): array
    {
        return [
            'ensure state contains only defined columns' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                    'sampleColumn2' => [ColumnsStateProvider::ORDER_FIELD_NAME => 1],
                    'extraColumn' => [],
                ],
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                ],
            ],
            'ensure state overrides default columns renderable setting' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                    'sampleColumn2' => [ColumnsStateProvider::RENDER_FIELD_NAME => false],
                ],
                'columns' => [
                    'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => false],
                    'sampleColumn2' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                ],
            ],
            'ensure renderable is set to true when is not defined' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::ORDER_FIELD_NAME => 0],
                    'sampleColumn2' => [ColumnsStateProvider::ORDER_FIELD_NAME => 1],
                ],
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                ],
            ],
            'ensure renderable is inherited from default column setting when is not defined' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::ORDER_FIELD_NAME => 0],
                ],
                'columns' => [
                    'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => false],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                ],
            ],
            'ensure renderable is sanitized' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => 0],
                    'sampleColumn2' => [ColumnsStateProvider::RENDER_FIELD_NAME => 1],
                    'sampleColumn3' => [ColumnsStateProvider::RENDER_FIELD_NAME => '1'],
                    'sampleColumn4' => [ColumnsStateProvider::RENDER_FIELD_NAME => 234],
                    'sampleColumn5' => [ColumnsStateProvider::RENDER_FIELD_NAME => '234'],
                    'sampleColumn6' => [ColumnsStateProvider::RENDER_FIELD_NAME => 'true'],
                    'sampleColumn7' => [ColumnsStateProvider::RENDER_FIELD_NAME => 'false'],
                    'sampleColumn8' => [ColumnsStateProvider::RENDER_FIELD_NAME => 'invalid'],
                ],
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                    'sampleColumn3' => [],
                    'sampleColumn4' => [],
                    'sampleColumn5' => [],
                    'sampleColumn6' => [],
                    'sampleColumn7' => [],
                    'sampleColumn8' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                    'sampleColumn3' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                    ],
                    'sampleColumn4' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 3,
                    ],
                    'sampleColumn5' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 4,
                    ],
                    'sampleColumn6' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 5,
                    ],
                    'sampleColumn7' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 6,
                    ],
                    'sampleColumn8' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 7,
                    ],
                ],
            ],
            'ensure order is filled automatically when not defined' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                    'sampleColumn2' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                ],
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                ],
            ],
            'ensure state overrides default columns order setting' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::ORDER_FIELD_NAME => 1],
                    'sampleColumn2' => [ColumnsStateProvider::ORDER_FIELD_NAME => 0],
                ],
                'columns' => [
                    'sampleColumn1' => [ColumnsStateProvider::ORDER_FIELD_NAME => 0],
                    'sampleColumn2' => [ColumnsStateProvider::ORDER_FIELD_NAME => 1],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                ],
            ],
            'ensure order is inherited from default column setting' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                    'sampleColumn2' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                ],
                'columns' => [
                    'sampleColumn1' => [ColumnsStateProvider::ORDER_FIELD_NAME => 1],
                    'sampleColumn2' => [ColumnsStateProvider::ORDER_FIELD_NAME => 2],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                    ],
                ],
            ],
            'ensure order is filled when not defined and not conflicts with other columns' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                    'sampleColumn2' => [ColumnsStateProvider::ORDER_FIELD_NAME => 3],
                    'sampleColumn3' => [ColumnsStateProvider::ORDER_FIELD_NAME => 2],
                    'sampleColumn4' => [ColumnsStateProvider::RENDER_FIELD_NAME => true],
                ],
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                    'sampleColumn3' => [],
                    'sampleColumn4' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 3,
                    ],
                    'sampleColumn3' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                    ],
                    'sampleColumn4' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                ],
            ],
            'ensure order is sanitized' => [
                'state' => [
                    'sampleColumn1' => [ColumnsStateProvider::ORDER_FIELD_NAME => '10'],
                    'sampleColumn2' => [ColumnsStateProvider::ORDER_FIELD_NAME => 'invalid'],
                    'sampleColumn3' => [ColumnsStateProvider::ORDER_FIELD_NAME => 'false'],
                    'sampleColumn4' => [ColumnsStateProvider::ORDER_FIELD_NAME => 'true'],
                    'sampleColumn5' => [ColumnsStateProvider::ORDER_FIELD_NAME => false],
                    'sampleColumn6' => [ColumnsStateProvider::ORDER_FIELD_NAME => true],
                ],
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                    'sampleColumn3' => [],
                    'sampleColumn4' => [],
                    'sampleColumn5' => [],
                    'sampleColumn6' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 10,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn3' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                    'sampleColumn4' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                    ],
                    'sampleColumn5' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 3,
                    ],
                    'sampleColumn6' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider minifiedStateDataProvider
     *
     * @param string $state
     * @param array $columns
     * @param array $expectedState
     */
    public function testGetStateWhenMinifiedParameters(string $state, array $columns, array $expectedState): void
    {
        $this->mockParametersState([], $state);

        $this->mockColumns($columns);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @return array
     */
    public function minifiedStateDataProvider(): array
    {
        return [
            'ensure state contains only defined columns' => [
                'state' => 'sampleColumn11.sampleColumn21.extraColumn1',
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ]
                ],
            ],
            'ensure state is properly handled when has invalid format' => [
                'state' => 'invalid!format!123?".sampleColumn1=2,sampleColumn21.',
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ]
                ],
            ],
            'ensure state contains correct order settings' => [
                'state' => 'sampleColumn21.sampleColumn11',
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ]
                ],
            ],
            'ensure state contains correct renderable settings' => [
                'state' => 'sampleColumn10.sampleColumn21',
                'columns' => [
                    'sampleColumn1' => [],
                    'sampleColumn2' => [],
                ],
                'expectedState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $columns
     * @param array $expectedState
     */
    public function testGetStateWhenCurrentGridView(array $state, array $columns, array $expectedState): void
    {
        $this->mockParametersState([], '');

        $this->mockColumns($columns);

        $this->mockGridName($gridName = 'sample-datagrid');
        $this->mockCurrentGridViewId($viewId = 'sample-view');

        $this->gridViewManager
            ->expects(self::once())
            ->method('getView')
            ->with($viewId, 1, $gridName)
            ->willReturn($gridView = $this->mockGridView('getColumnsData', $state));

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $columns
     * @param array $expectedState
     */
    public function testGetStateWhenDefaultGridView(array $state, array $columns, array $expectedState): void
    {
        $this->mockParametersState([], '');

        $this->mockColumns($columns);

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
            ->willReturn($gridView = $this->mockGridView('getColumnsData', $state));

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    public function testGetStateWhenGridViewsDisabled(): void
    {
        [$columns, $expectedState] = array_values($this->getDefaultColumnsStates());

        $this->mockParametersState([], '');

        $this->assertGridViewsDisabled();

        $this->mockColumns($columns);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    public function testGetStateWhenDefaultColumnsState(): void
    {
        [$columns, $expectedState] = array_values($this->getDefaultColumnsStates());

        $this->mockParametersState([], '');

        $this->assertNoCurrentNoDefaultGridView();

        $this->mockColumns($columns);

        $actualState = $this->provider->getState($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    /**
     * @return array
     */
    private function getDefaultColumnsStates(): array
    {
        return [
            'columns' => [
                'sampleColumn1' => [],
                'sampleColumn2' => [ColumnsStateProvider::ORDER_FIELD_NAME => 2],
                'sampleColumn3' => [ColumnsStateProvider::RENDER_FIELD_NAME => false],
                'sampleColumn4' => [],
                'sampleColumn5' => [],
            ],
            'expectedState' => [
                'sampleColumn1' => [
                    ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                ],
                'sampleColumn2' => [
                    ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                ],
                'sampleColumn3' => [
                    ColumnsStateProvider::RENDER_FIELD_NAME => false,
                    ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                ],
                'sampleColumn4' => [
                    ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ColumnsStateProvider::ORDER_FIELD_NAME => 3,
                ],
                'sampleColumn5' => [
                    ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ColumnsStateProvider::ORDER_FIELD_NAME => 4,
                ],
            ],
        ];
    }

    /**
     * @dataProvider stateDataProvider
     *
     * @param array $state
     * @param array $columns
     * @param array $expectedState
     */
    public function testGetStateFromParameters(array $state, array $columns, array $expectedState): void
    {
        $this->mockParametersState($state, '');

        $this->mockColumns($columns);

        $actualState = $this->provider->getStateFromParameters($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedState, $actualState);
    }

    public function testGetDefaultState(): void
    {
        [$columns, $expectedState] = array_values($this->getDefaultColumnsStates());

        $this->mockColumns($columns);

        $actualState = $this->provider->getDefaultState($this->datagridConfiguration);

        self::assertEquals($expectedState, $actualState);
    }
}
