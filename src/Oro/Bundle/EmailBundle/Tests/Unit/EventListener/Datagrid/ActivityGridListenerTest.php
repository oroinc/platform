<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\EventListener\Datagrid\ActivityGridListener;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

class ActivityGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityGridListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityRoutingHelper;

    protected function setUp()
    {
        $this->activityManager     = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Entity\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ActivityGridListener(
            $this->activityManager,
            $this->entityRoutingHelper
        );
    }

    public function testOnBuildAfter()
    {
        $encodedEntityClass = 'Test_Entity';
        $entityClass        = 'Test\Entity';
        $entityId           = 123;

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->will(
                $this->returnValue(
                    new ParameterBag(['entityClass' => $encodedEntityClass, 'entityId' => $entityId])
                )
            );
        $this->entityRoutingHelper->expects($this->once())
            ->method('decodeClassName')
            ->with($encodedEntityClass)
            ->will($this->returnValue($entityClass));

        $this->activityManager->expects($this->once())
            ->method('addFilterByTargetEntity')
            ->with(
                $this->identicalTo($qb),
                $entityClass,
                $entityId
            );

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
