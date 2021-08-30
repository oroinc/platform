<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;
use Oro\Bundle\EmailBundle\EventListener\Datagrid\ActivityGridListener;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

class ActivityGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActivityGridListener */
    private $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailGridHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    protected function setUp(): void
    {
        $this->emailGridHelper = $this->createMock(EmailGridHelper::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);

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
        $datasource = $this->createMock(OrmDatasource::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $this->entityRoutingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($encodedEntityClass)
            ->willReturn($entityClass);

        $this->emailGridHelper->expects($this->once())
            ->method('updateDatasource')
            ->with($this->identicalTo($datasource), $entityId, $entityClass);
        $this->emailGridHelper->expects($this->once())
            ->method('isUserEntity')
            ->with($entityClass)
            ->willReturn(false);
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
        $datasource = $this->createMock(OrmDatasource::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $this->entityRoutingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($encodedEntityClass)
            ->willReturn($entityClass);

        $this->emailGridHelper->expects($this->once())
            ->method('updateDatasource')
            ->with($this->identicalTo($datasource), $entityId, $entityClass);
        $this->emailGridHelper->expects($this->once())
            ->method('isUserEntity')
            ->with($entityClass)
            ->willReturn(true);
        $this->emailGridHelper->expects($this->once())
            ->method('handleRefresh')
            ->with($this->identicalTo($parameters), $entityId);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
