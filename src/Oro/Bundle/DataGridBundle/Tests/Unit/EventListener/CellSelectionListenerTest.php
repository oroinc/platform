<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\EventListener\CellSelectionListener;
use Oro\Bundle\DataGridBundle\Exception\LogicException;

class CellSelectionListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var BuildAfter|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject */
    private $datasource;

    /** @var CellSelectionListener */
    private $listener;

    protected function setUp(): void
    {
        $this->event = $this->createMock(BuildAfter::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datasource = $this->createMock(OrmDatasource::class);

        $this->listener = new CellSelectionListener();
    }

    /**
     * @dataProvider onBuildAfterDataProvider
     */
    public function testOnBuildAfter(array $config, array $expectedConfig)
    {
        $config = DatagridConfiguration::create($config);

        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasource);

        $this->datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->listener->onBuildAfter($this->event);

        $this->assertEquals($expectedConfig, $config->toArray());
    }

    public function onBuildAfterDataProvider(): array
    {
        return [
            'applicable config' => [
                'config' => [
                    'options' => [
                        'cellSelection' => [
                            'dataField' => 'id',
                            'columnName' => ['first', 'second'],
                            'selector' => 'changeset'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'options' => [
                        'cellSelection' => [
                            'dataField' => 'id',
                            'columnName' => ['first', 'second'],
                            'selector' => 'changeset'
                        ],
                        'jsmodules' => [
                            'orodatagrid/js/datagrid/listener/change-editable-cell-listener'
                        ],
                    ],
                ],
            ],
            'not applicable config' => [
                'config' => [
                    'options' => [],
                ],
                'expectedConfig' => [
                    'options' => [],
                ],
            ],
            'append frontend module' => [
                'config' => [
                    'options' => [
                        'cellSelection' => [
                            'dataField' => 'id',
                            'columnName' => ['first', 'second'],
                            'selector' => 'changeset'
                        ],
                        'jsmodules' => [
                            'some-module'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'cellSelection' => [
                            'dataField' => 'id',
                            'columnName' => ['first', 'second'],
                            'selector' => 'changeset'
                        ],
                        'jsmodules' => [
                            'some-module',
                            'orodatagrid/js/datagrid/listener/change-editable-cell-listener'
                        ],
                    ],
                ]
            ],
            'frontend module exist' => [
                'config' => [
                    'options' => [
                        'cellSelection' => [
                            'dataField' => 'id',
                            'columnName' => ['first', 'second'],
                            'selector' => 'changeset'
                        ],
                        'jsmodules' => [
                            'orodatagrid/js/datagrid/listener/change-editable-cell-listener'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'cellSelection' => [
                            'dataField' => 'id',
                            'columnName' => ['first', 'second'],
                            'selector' => 'changeset'
                        ],
                        'jsmodules' => [
                            'orodatagrid/js/datagrid/listener/change-editable-cell-listener'
                        ],
                    ],
                ]
            ],
        ];
    }

    public function testOnBuildAfterWorksSkippedForNotApplicableDatasource()
    {
        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        $datasource = $this->createMock(DatasourceInterface::class);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->datagrid->expects($this->never())
            ->method('getConfig')
            ->willReturn($datasource);

        $this->datasource->expects($this->never())
            ->method($this->anything());

        $this->listener->onBuildAfter($this->event);
    }

    public function testOnBuildAfterException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('cellSelection options `columnName`, `selector` are required');

        $config = DatagridConfiguration::create([
            'options' => [
                'cellSelection' => [
                    'dataField' => 'id'
                ]
            ]
        ]);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasource);

        $this->datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        $this->listener->onBuildAfter($this->event);
    }
}
