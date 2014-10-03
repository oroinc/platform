<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\EventListener\DatasourceBindParametersListener;

class DatasourceBindParametersListenerTest extends \PHPUnit_Framework_TestCase
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
     * @var DatasourceBindParametersListener
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
        $this->listener = new DatasourceBindParametersListener();
    }

    /**
     * @dataProvider onBuildAfterDataProvider
     * @param array $config
     * @param array $expectedBindParameters
     */
    public function testOnBuildAfterWorks(array $config, array $expectedBindParameters = null)
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
            $this->datasource->expects($this->once())
                ->method('bindParameters')
                ->with($expectedBindParameters);
        } else {
            $this->datasource->expects($this->never())->method($this->anything());
        }

        $this->listener->onBuildAfter($this->event);
    }

    /**
     * @return array
     */
    public function onBuildAfterDataProvider()
    {
        return [
            'applicable config' => [
                'config' => [
                    'source' => [
                        'bind_parameters' => [
                            'foo' => 'bar'
                        ]
                    ],
                ],
                'expectedBindParameters' => ['foo' => 'bar'],
            ],
            'empty bind parameters' => [
                'config' => [
                    'source' => [
                        'bind_parameters' => []
                    ],
                ],
                'expectedBindParameters' => null,
            ],
            'empty option' => [
                'config' => [
                    'source' => [],
                ],
                'expectedBindParameters' => null,
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
