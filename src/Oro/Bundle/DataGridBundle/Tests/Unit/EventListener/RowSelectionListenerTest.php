<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\EventListener\RowSelectionListener;

class RowSelectionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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
     * @var RowSelectionListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->event = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Event\\BuildAfter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->datagrid = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
        $this->datasource = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datasource\\Orm\\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new RowSelectionListener();
    }

    /**
     * @dataProvider onBuildAfterDataProvider
     */
    public function testOnBuildAfterWorks(array $config, array $expectedConfig, $expectedBindParameters)
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

        if ($expectedBindParameters) {
            $expectedBindParameters = [
                'data_in' => [
                    'name' => RowSelectionListener::GRID_PARAM_DATA_IN,
                    'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.' . RowSelectionListener::GRID_PARAM_DATA_IN,
                    'default' => [0],
                ],
                'data_not_in' => [
                    'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                    'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                        . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                    'default' => [0],
                ],
            ];
            $this->datasource->expects($this->once())
                ->method('bindParameters')
                ->with($expectedBindParameters);
        } else {
            $this->datasource->expects($this->never())->method($this->anything());
        }

        $this->listener->onBuildAfter($this->event);

        $this->assertEquals($expectedConfig, $config->toArray());
    }

    public function onBuildAfterDataProvider()
    {
        return [
            'applicable config' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ]
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'expectedBindParameters' => true,
            ],
            'append frontend module' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'some-module'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'some-module',
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'expectedBindParameters' => true,
            ],
            'frontend module exist' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'expectedBindParameters' => true,
            ],
            'not applicable config' => [
                'config' => [
                    'options' => [],
                ],
                'expectedConfig' => [
                    'options' => [],
                ],
                'expectedBindParameters' => false,
            ],
        ];
    }

    public function testOnBuildAfterWorksSkippedForNotApplicableDatasource()
    {
        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($this->datagrid));

        $datasource = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datasource\\DatasourceInterface');

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $this->datagrid->expects($this->never())
            ->method('getConfig')
            ->will($this->returnValue($datasource));

        $this->datasource->expects($this->never())->method($this->anything());

        $this->listener->onBuildAfter($this->event);
    }
}
