<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataAuditBundle\EventListener\AuditGridImpersonationListener;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuditGridImpersonationListenerTest extends TestCase
{
    private DatagridInterface&MockObject $datagrid;
    private EntityRepository&MockObject $repository;
    private AuditGridImpersonationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repository);

        $this->listener = new AuditGridImpersonationListener($doctrineHelper);
    }

    public function testAddDataSupportNoData(): void
    {
        $event = new OrmResultAfter($this->datagrid, []);
        $this->listener->addImpersonationSupport($event);
    }

    public function testAddDataSupportNoIds(): void
    {
        $this->repository->expects($this->never())
            ->method('findBy');

        $record = new ResultRecord([]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->listener->addImpersonationSupport($event);

        $this->assertNull($record->getValue('impersonation'));
    }

    public function testAddDataSupportNullIds(): void
    {
        $this->repository->expects($this->never())
            ->method('findBy');

        $record = new ResultRecord(['impersonation' => null]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->listener->addImpersonationSupport($event);

        $this->assertNull($record->getValue('impersonation'));
    }

    public function testAddDataSupportNoImpersonations(): void
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $record = new ResultRecord(['impersonation' => 24]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->listener->addImpersonationSupport($event);

        $this->assertNull($record->getValue('impersonation'));
    }

    public function testAddDataSupportSetImpersonation(): void
    {
        $impersonation = $this->createMock(Impersonation::class);
        $impersonation->expects($this->any())
            ->method('getId')
            ->willReturn(24);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$impersonation]);

        $record = new ResultRecord(['impersonation' => 24]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->listener->addImpersonationSupport($event);

        $this->assertInstanceOf(Impersonation::class, $record->getValue('impersonation'));
        $this->assertSame($impersonation->getId(), $record->getValue('impersonation')->getId());
    }

    public function testAddDataSupportSetNoMatchingImpersonation(): void
    {
        $impersonation = $this->createMock(Impersonation::class);
        $impersonation->expects($this->any())
            ->method('getId')
            ->willReturn(24);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$impersonation]);

        $record = new ResultRecord(['impersonation' => 25]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->listener->addImpersonationSupport($event);

        $this->assertNull($record->getValue('impersonation'));
    }

    public function testAddDataSupportMultipleImpersonations(): void
    {
        $impersonation = $this->createMock(Impersonation::class);
        $impersonation->expects($this->any())
            ->method('getId')
            ->willReturn(25);
        $impersonation2 = $this->createMock(Impersonation::class);
        $impersonation2->expects($this->any())
            ->method('getId')
            ->willReturn(24);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [24, 25]])
            ->willReturn([$impersonation2, $impersonation]);

        $record = new ResultRecord(['impersonation' => 25]);
        $record2 = new ResultRecord(['impersonation' => 24]);
        $event = new OrmResultAfter($this->datagrid, [$record, $record2]);
        $this->listener->addImpersonationSupport($event);

        $this->assertInstanceOf(Impersonation::class, $record->getValue('impersonation'));
        $this->assertSame($impersonation->getId(), $record->getValue('impersonation')->getId());

        $this->assertInstanceOf(Impersonation::class, $record2->getValue('impersonation'));
        $this->assertSame($impersonation2->getId(), $record2->getValue('impersonation')->getId());
    }
}
