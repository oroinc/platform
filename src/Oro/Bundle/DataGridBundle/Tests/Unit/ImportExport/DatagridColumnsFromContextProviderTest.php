<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridColumnsFromContextProvider;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

class DatagridColumnsFromContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridManager;

    /** @var DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $columnsStateProvider;

    /** @var DatagridColumnsFromContextProvider */
    private $datagridColumnsFromContextProvider;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    protected function setUp()
    {
        $this->datagridManager = $this->createMock(Manager::class);
        $this->columnsStateProvider = $this->createMock(DatagridStateProviderInterface::class);

        $this->datagridColumnsFromContextProvider = new DatagridColumnsFromContextProvider(
            $this->datagridManager,
            $this->columnsStateProvider
        );

        $this->context = $this->createMock(ContextInterface::class);
    }

    public function testGetColumnsFromContextWhenColumnsWhenNoGridName(): void
    {
        $this->mockColumnsInContext($expectedColumns = ['sampleColumn1' => ['label' => 'Sample column']]);

        $this->context
            ->expects(self::once())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(false);

        $columns = $this->datagridColumnsFromContextProvider->getColumnsFromContext($this->context);

        self::assertEquals($expectedColumns, $columns);
    }

    /**
     * @param array $expectedColumns
     */
    private function mockColumnsInContext(array $expectedColumns): void
    {
        $this->context
            ->expects(self::once())
            ->method('getValue')
            ->with('columns')
            ->willReturn($expectedColumns);
    }

    public function testGetColumnsFromContextWhenNoColumnsWhenNoGridName(): void
    {
        $this->mockColumnsInContext([]);

        $this->context
            ->expects(self::once())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(false);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Configuration of datagrid export processor must contain "gridName" or "columns" options.'
        );

        $this->datagridColumnsFromContextProvider->getColumnsFromContext($this->context);
    }

    public function testGetColumnsFromContextWhenNoColumnsWhenGridNameWhenNoColumns(): void
    {
        $this->mockColumnsInContext([]);

        $this->mockGridName($gridName = 'sampleGridName');

        $this->datagridManager
            ->expects(self::once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->willReturn($datagridConfig = $this->createMock(DatagridConfiguration::class));

        $datagridConfig
            ->expects(self::once())
            ->method('offsetGet')
            ->with(Configuration::COLUMNS_KEY)
            ->willReturn([]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Configuration of datagrid export processor must contain "gridName" or "columns" options.'
        );

        $this->datagridColumnsFromContextProvider->getColumnsFromContext($this->context);
    }

    /**
     * @param string $gridName
     */
    private function mockGridName(string $gridName): void
    {
        $this->context
            ->expects(self::any())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $this->context
            ->expects(self::any())
            ->method('getOption')
            ->with('gridName')
            ->willReturn($gridName);
    }

    public function testGetColumnsFromContextWhenNoColumnsWhenGridNameWhenNoParams(): void
    {
        $this->mockColumnsInContext([]);

        $this->context
            ->expects(self::any())
            ->method('hasOption')
            ->willReturnMap([
                ['gridName', true],
                ['gridParameters', false],
            ]);

        $this->context
            ->expects(self::any())
            ->method('getOption')
            ->with('gridName')
            ->willReturn($gridName = 'sampleGridName');

        $this->datagridManager
            ->expects(self::once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->willReturn($datagridConfig = $this->createMock(DatagridConfiguration::class));

        $expectedColumns = ['sampleColumn1' => ['label' => 'Sample column']];

        $datagridConfig
            ->expects(self::once())
            ->method('offsetGet')
            ->with(Configuration::COLUMNS_KEY)
            ->willReturn($expectedColumns);

        $columns = $this->datagridColumnsFromContextProvider->getColumnsFromContext($this->context);

        self::assertEquals($expectedColumns, $columns);
    }

    /**
     * @dataProvider getColumnsFromContextWhenColumnsWhenParamsDataProvider
     *
     * @param array $gridColumns
     * @param array $columnsState
     * @param array $expectedColumns
     */
    public function testGetColumnsFromContextWhenColumnsWhenParams(
        array $gridColumns,
        array $columnsState,
        array $expectedColumns
    ): void {
        $this->mockColumnsInContext($gridColumns);

        $this->context
            ->expects(self::any())
            ->method('hasOption')
            ->willReturnMap([
                ['gridName', true],
                ['gridParameters', true],
            ]);

        $gridName = 'sampleGridName';
        $gridParameters = $this->createMock(ParameterBag::class);
        $this->context
            ->expects(self::any())
            ->method('getOption')
            ->willReturnMap([
                ['gridName', null, $gridName],
                ['gridParameters', null, $gridParameters],
            ]);

        $this->datagridManager
            ->expects(self::once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->willReturn($datagridConfig = $this->createMock(DatagridConfiguration::class));

        $datagridConfig
            ->expects(self::once())
            ->method('offsetSet')
            ->with(Configuration::COLUMNS_KEY)
            ->willReturn($gridColumns);

        $this->columnsStateProvider
            ->expects(self::once())
            ->method('getState')
            ->with($datagridConfig, $gridParameters)
            ->willReturn($columnsState);

        $columns = $this->datagridColumnsFromContextProvider->getColumnsFromContext($this->context);

        self::assertEquals($expectedColumns, $columns);
    }

    /**
     * @return array
     */
    public function getColumnsFromContextWhenColumnsWhenParamsDataProvider(): array
    {
        return [
            'column properties are set with values from state' => [
                'gridColumns' => [
                    'sampleColumn1' => ['label' => 'Sample column 1'],
                ],
                'columnsState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ],
                ],
                'expectedColumns' => [
                    'sampleColumn1' => [
                        'label' => 'Sample column 1',
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ]
                ],
            ],
            'column properties are updated with values from state' => [
                'gridColumns' => [
                    'sampleColumn1' => [
                        'label' => 'Sample column 1',
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                    ],
                ],
                'columnsState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ],
                ],
                'expectedColumns' => [
                    'sampleColumn1' => [
                        'label' => 'Sample column 1',
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ]
                ],
            ],
            'columns are sorted by order' => [
                'gridColumns' => [
                    'sampleColumn1' => ['label' => 'Sample column 1'],
                    'sampleColumn2' => ['label' => 'Sample column 2'],
                ],
                'columnsState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ],
                ],
                'expectedColumns' => [
                    'sampleColumn2' => [
                        'label' => 'Sample column 2',
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ],
                    'sampleColumn1' => [
                        'label' => 'Sample column 1',
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ]
                ],
            ],
            'non-renderable columns are excluded' => [
                'gridColumns' => [
                    'sampleColumn1' => ['label' => 'Sample column 1'],
                    'sampleColumn2' => ['label' => 'Sample column 2'],
                ],
                'columnsState' => [
                    'sampleColumn1' => [
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                    ],
                    'sampleColumn2' => [
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ],
                ],
                'expectedColumns' => [
                    'sampleColumn2' => [
                        'label' => 'Sample column 2',
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                    ],
                ],
            ],
        ];
    }
}
