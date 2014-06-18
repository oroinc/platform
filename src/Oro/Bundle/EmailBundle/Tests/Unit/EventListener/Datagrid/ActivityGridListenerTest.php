<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\EventListener\Datagrid\ActivityGridListener;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;

class ActivityGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityGridListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $activityHelper;

    protected function setUp()
    {
        $this->activityHelper = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Tools\ActivityHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ActivityGridListener($this->activityHelper);
    }

    public function testOnBuildAfter()
    {
        $entity = new SomeEntity();
        $qb     = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
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
            ->will($this->returnValue(new ParameterBag(['entity' => $entity])));

        $this->activityHelper->expects($this->once())
            ->method('addFilterByTargetEntity')
            ->with(
                $this->identicalTo($qb),
                $this->identicalTo($entity)
            );

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
