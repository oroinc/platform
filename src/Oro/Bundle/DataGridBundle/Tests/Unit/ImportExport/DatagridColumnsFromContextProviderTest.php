<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridColumnsFromContextProvider;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

class DatagridColumnsFromContextProviderTest extends \PHPUnit\Framework\TestCase
{
    private DatagridManager|\PHPUnit\Framework\MockObject\MockObject $datagridManager;

    private DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject $columnsStateProvider;

    private DatagridColumnsFromContextProvider $datagridColumnsFromContextProvider;

    protected function setUp(): void
    {
        $this->datagridManager = $this->createMock(DatagridManager::class);
        $this->columnsStateProvider = $this->createMock(DatagridStateProviderInterface::class);

        $this->datagridColumnsFromContextProvider = new DatagridColumnsFromContextProvider(
            $this->datagridManager,
            $this->columnsStateProvider
        );
    }

    public function testGetColumnsFromContextWhenNoGridName(): void
    {
        $this->expectExceptionObject(
            new InvalidConfigurationException(
                'Configuration of datagrid export processor must contain "gridName" option.'
            )
        );

        $this->datagridColumnsFromContextProvider->getColumnsFromContext(new Context([]));
    }

    /**
     * @dataProvider getColumnsFromContextDataProvider
     */
    public function testGetColumnsFromContextWhenColumnsWhenParams(
        array $gridColumns,
        array $columnsState,
        array $expectedColumns
    ): void {
        $gridName = 'sampleGridName';
        $gridParameters = new ParameterBag([]);
        $context = new Context(['gridName' => $gridName, 'gridParameters' => $gridParameters]);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagridConfig = DatagridConfiguration::create([Configuration::COLUMNS_KEY => $gridColumns]);
        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfig);
        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($gridParameters);
        $this->datagridManager->expects(self::once())
            ->method('getDatagrid')
            ->with($gridName, $gridParameters)
            ->willReturn($datagrid);

        $this->columnsStateProvider->expects(self::once())
            ->method('getState')
            ->with($datagridConfig, $gridParameters)
            ->willReturn($columnsState);

        $columns = $this->datagridColumnsFromContextProvider->getColumnsFromContext($context);

        self::assertEquals($expectedColumns, $columns);
    }

    public function getColumnsFromContextDataProvider(): array
    {
        return [
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
                    ],
                    'sampleColumn1' => [
                        'label' => 'Sample column 1',
                    ],
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
                    ],
                ],
            ],
        ];
    }
}
