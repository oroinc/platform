<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\EventListener\SelectCellListener;

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
     * @var SelectCellListener
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

        $this->listener = new SelectCellListener();
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
                        'selectCell' => [
                            'dataField' => 'id',
                            'fields' => ['first', 'second'],
                            'changeset' => 'changeset'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'options' => [
                        'selectCell' => [
                            'dataField' => 'id',
                            'fields' => ['first', 'second'],
                            'changeset' => 'changeset'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/select-cell-form-listener'
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
                        'selectCell' => [
                            'dataField' => 'id',
                            'fields' => ['first', 'second'],
                            'changeset' => 'changeset'
                        ],
                        'requireJSModules' => [
                            'some-module'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'selectCell' => [
                            'dataField' => 'id',
                            'fields' => ['first', 'second'],
                            'changeset' => 'changeset'
                        ],
                        'requireJSModules' => [
                            'some-module',
                            'orodatagrid/js/datagrid/listener/select-cell-form-listener'
                        ],
                    ],
                ]
            ],
            'frontend module exist' => [
                'config' => [
                    'options' => [
                        'selectCell' => [
                            'dataField' => 'id',
                            'fields' => ['first', 'second'],
                            'changeset' => 'changeset'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/select-cell-form-listener'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'selectCell' => [
                            'dataField' => 'id',
                            'fields' => ['first', 'second'],
                            'changeset' => 'changeset'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/select-cell-form-listener'
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
     * @expectedExceptionMessage SelectCell options fields, changeset are required
     */
    public function testOnBuildAfterException()
    {
        $config = DatagridConfiguration::create([
            'options' => [
                'selectCell' => [
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
