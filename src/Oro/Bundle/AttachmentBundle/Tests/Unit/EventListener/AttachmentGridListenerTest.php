<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\EventListener\AttachmentGridListener;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestGridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class AttachmentGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentGridListener */
    protected $listener;

    public function setUp()
    {
        $this->listener = new AttachmentGridListener(['entityId']);
    }

    public function testOnBuildBefore()
    {
        $gridConfig = new TestGridConfiguration();

        $parameters = new ParameterBag(['entityField' => 'testField']);
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));

        $event = new BuildBefore($datagrid, $gridConfig);
        $this->listener->onBuildBefore($event);

        $leftJoins = $gridConfig->offsetGetByPath('[source][query][join][left]');
        $this->assertEquals(
            [
                [
                    'join' => 'attachment.testField',
                    'alias' => 'entity'
                ]
            ],
            $leftJoins
        );
    }

    public function testOnBuildAfter()
    {
        $entityId = 458;

        $parameters = new ParameterBag(['entityId' => $entityId]);
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $qb->expects($this->once())
            ->method('setParameters')
            ->with(
                [
                    'entityId' => $entityId
                ]
            );

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
