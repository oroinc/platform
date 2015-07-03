<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\EventListener\CellSelectionListener;

class SelectCellListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Oro\Bundle\DataGridBundle\Event\BuildAfter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagrid;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $datasource;

    /**
     * @var CellSelectionListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildAfter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CellSelectionListener();
    }

    protected function tearDown()
    {
        unset($this->event, $this->datagrid, $this->datasource, $this->listener);
    }

    /**
     * @dataProvider onBuildAfterDataProvider
     * @param array $config
     * @param array $expectedConfig
     */
    public function testOnBuildAfter(array $config, array $expectedConfig)
    {
        $config = DatagridConfiguration::create($config);
    
        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($this->datagrid));
    
        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));
    
        $this->datagrid->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));
    
        $this->listener->onBuildAfter($this->event);
    
        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @return array
     */
    public function onBuildAfterDataProvider()
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
                        'requireJSModules' => [
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
                        'requireJSModules' => [
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
                        'requireJSModules' => [
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
                        'requireJSModules' => [
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
                        'requireJSModules' => [
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
            ->will($this->returnValue($this->datagrid));

        $datasource = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $this->datagrid->expects($this->never())
            ->method('getConfig')
            ->will($this->returnValue($datasource));

        $this->datasource->expects($this->never())->method($this->anything());

        $this->listener->onBuildAfter($this->event);
    }

    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\LogicException
     * @expectedExceptionMessage cellSelection options `columnName`, `selector` are required
     */
    public function testOnBuildAfterException()
    {
        $config = DatagridConfiguration::create([
            'options' => [
                'cellSelection' => [
                    'dataField' => 'id'
                ]
            ]
        ]);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));

        $this->datagrid->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($this->datagrid));

        $this->listener->onBuildAfter($this->event);
    }
}
