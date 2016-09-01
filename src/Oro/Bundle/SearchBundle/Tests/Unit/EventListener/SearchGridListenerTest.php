<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\SearchBundle\EventListener\SearchGridListener;

class SearchGridListenerTest extends \PHPUnit_Framework_TestCase
{
    const FROM_TEST_VALUE = 'FROM_TEST_VALUE';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $datasourceMock;

    /**
     * @var SearchGridListener
     */
    protected $listener;

    public function setUp()
    {
        $this->eventMock = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildAfter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagridMock = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface')
            ->getMock();

        $this->eventMock
            ->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagridMock);

        $this->listener = new SearchGridListener();
    }

    public function testOnBuildAfter()
    {
        $this->datasourceMock = $this->getMockBuilder('Oro\Bundle\SearchBundle\Datasource\SearchDatasource')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagridMock
            ->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasourceMock);

        $config = DatagridConfiguration::create([
            'source' => [
                'query' => [
                    'from' => self::FROM_TEST_VALUE,
                ]
            ]
        ]);
        $this->datagridMock
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $queryMock = $this->getMockBuilder('Oro\Bundle\SearchBundle\Extension\SearchQueryInterface')
            ->getMock();
        $queryMock
            ->expects($this->once())
            ->method('from')
            ->with(self::FROM_TEST_VALUE);

        $this->datasourceMock
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($queryMock);

        $this->listener->onBuildAfter($this->eventMock);
    }

    public function testOnBuildAfterEmptyFrom()
    {
        $this->datasourceMock = $this->getMockBuilder('Oro\Bundle\SearchBundle\Datasource\SearchDatasource')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagridMock
            ->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasourceMock);

        $config = DatagridConfiguration::create([]);
        $this->datagridMock
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->datasourceMock
            ->expects($this->never())
            ->method('getQuery');

        $this->listener->onBuildAfter($this->eventMock);
    }

    public function testOnBuildAfterNonSearchDatasource()
    {
        $this->datasourceMock = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface')
            ->getMock();

        $this->datagridMock
            ->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasourceMock);

        $this->datagridMock
            ->expects($this->never())
            ->method('getConfig');

        $this->listener->onBuildAfter($this->eventMock);
    }
}
