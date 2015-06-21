<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\EventListener\Datagrid\ActivityGridListener;

class ActivityGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityGridListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailGridHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityRoutingHelper;

    protected function setUp()
    {
        $this->emailGridHelper     = $this->getMockBuilder('Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ActivityGridListener(
            $this->emailGridHelper,
            $this->entityRoutingHelper
        );
    }

    public function testOnBuildAfter()
    {
        $encodedEntityClass = 'Test_Entity';
        $entityClass        = 'Test\Entity';
        $entityId           = 123;

        $parameters = new ParameterBag(['entityClass' => $encodedEntityClass, 'entityId' => $entityId]);
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $this->entityRoutingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($encodedEntityClass)
            ->will($this->returnValue($entityClass));

        $this->emailGridHelper->expects($this->once())
            ->method('updateDatasource')
            ->with(
                $this->identicalTo($datasource),
                $entityId,
                $entityClass
            );
        $this->emailGridHelper->expects($this->once())
            ->method('isUserEntity')
            ->with($entityClass)
            ->will($this->returnValue(false));
        $this->emailGridHelper->expects($this->never())
            ->method('handleRefresh');

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterForUser()
    {
        $encodedEntityClass = 'Test_Entity';
        $entityClass        = 'Test\Entity';
        $entityId           = 123;

        $parameters = new ParameterBag(['entityClass' => $encodedEntityClass, 'entityId' => $entityId]);
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $this->entityRoutingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($encodedEntityClass)
            ->will($this->returnValue($entityClass));

        $this->emailGridHelper->expects($this->once())
            ->method('updateDatasource')
            ->with(
                $this->identicalTo($datasource),
                $entityId,
                $entityClass
            );
        $this->emailGridHelper->expects($this->once())
            ->method('isUserEntity')
            ->with($entityClass)
            ->will($this->returnValue(true));
        $this->emailGridHelper->expects($this->once())
            ->method('handleRefresh')
            ->with($this->identicalTo($parameters), $entityId);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
