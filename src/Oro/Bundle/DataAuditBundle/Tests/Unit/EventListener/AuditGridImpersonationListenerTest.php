<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataAuditBundle\EventListener\AuditGridImpersonationListener;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\Impersonation;

class AuditGridImpersonationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var AuditGridImpersonationListener */
    private $listener;

    /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $datagrid;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->any())->method('getEntityRepository')->willReturn($this->repository);

        $this->listener = new AuditGridImpersonationListener($this->doctrineHelper);

        $this->datagrid = $this->createMock(DatagridInterface::class);
    }

    public function testAddDataSupportNoData()
    {
        $event = new OrmResultAfter($this->datagrid, []);
        $this->listener->addImpersonationSupport($event);
    }

    public function testAddDataSupportNoIds()
    {
        $record = new ResultRecord([]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->never())->method('findBy');
        $this->listener->addImpersonationSupport($event);
        $this->assertNull($record->getValue('impersonation'));
    }

    public function testAddDataSupportNullIds()
    {
        $record = new ResultRecord(['impersonation' => null]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->never())->method('findBy');
        $this->listener->addImpersonationSupport($event);
        $this->assertNull($record->getValue('impersonation'));
    }

    public function testAddDataSupportNoImpresonations()
    {
        $record = new ResultRecord(['impersonation' => 24]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->once())->method('findBy')->willReturn([]);
        $this->listener->addImpersonationSupport($event);
        $this->assertNull($record->getValue('impersonation'));
    }

    public function testAddDataSupportSetImpersonation()
    {
        $impresonation = $this->createPartialMock(Impersonation::class, ['getId']);
        $impresonation->expects($this->any())->method('getId')->willReturn(24);

        $record = new ResultRecord(['impersonation' => 24]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->once())->method('findBy')->willReturn([$impresonation]);
        $this->listener->addImpersonationSupport($event);
        $this->assertInstanceOf(Impersonation::class, $record->getValue('impersonation'));
        $this->assertSame($impresonation->getId(), $record->getValue('impersonation')->getId());
    }

    public function testAddDataSupportSetNoMatchingImpersonation()
    {
        $impresonation = $this->createPartialMock(Impersonation::class, ['getId']);
        $impresonation->expects($this->any())->method('getId')->willReturn(24);

        $record = new ResultRecord(['impersonation' => 25]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->once())->method('findBy')->willReturn([$impresonation]);
        $this->listener->addImpersonationSupport($event);
        $this->assertNull($record->getValue('impersonation'));
    }

    public function testAddDataSupportMultipleImpersonations()
    {
        $impresonation = $this->createPartialMock(Impersonation::class, ['getId']);
        $impresonation->expects($this->any())->method('getId')->willReturn(25);
        $impresonation2 = $this->createPartialMock(Impersonation::class, ['getId']);
        $impresonation2->expects($this->any())->method('getId')->willReturn(24);

        $record = new ResultRecord(['impersonation' => 25]);
        $record2 = new ResultRecord(['impersonation' => 24]);
        $event = new OrmResultAfter($this->datagrid, [$record, $record2]);
        $this->repository->expects($this->once())->method('findBy')->with(['id' => [24, 25]])
            ->willReturn([$impresonation2, $impresonation]);
        $this->listener->addImpersonationSupport($event);

        $this->assertInstanceOf(Impersonation::class, $record->getValue('impersonation'));
        $this->assertSame($impresonation->getId(), $record->getValue('impersonation')->getId());

        $this->assertInstanceOf(Impersonation::class, $record2->getValue('impersonation'));
        $this->assertSame($impresonation2->getId(), $record2->getValue('impersonation')->getId());
    }
}
