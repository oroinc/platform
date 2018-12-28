<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataAuditBundle\EventListener\AuditGridOrganizationListener;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

class AuditGridOrganizationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var AuditGridOrganizationListener */
    private $listener;

    /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $datagrid;

    /** @var OrganizationRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repository = $this->createMock(OrganizationRepository::class);
        $this->doctrineHelper->expects($this->any())->method('getEntityRepository')->willReturn($this->repository);

        $this->listener = new AuditGridOrganizationListener($this->doctrineHelper);

        $this->datagrid = $this->createMock(DatagridInterface::class);
    }

    public function testAddDataSupportNoData()
    {
        $event = new OrmResultAfter($this->datagrid, []);
        $this->listener->addOrganizationSupport($event);
    }

    public function testAddDataSupportNoIds()
    {
        $record = new ResultRecord([]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->never())->method('find');
        $this->listener->addOrganizationSupport($event);
        $this->assertNull($record->getValue('organization'));
    }

    public function testAddDataSupportNoOrganization()
    {
        $record = new ResultRecord(['organization' => 1]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->once())->method('find')->willReturn(null);
        $this->listener->addOrganizationSupport($event);
        $this->assertNull($record->getValue('organization'));
    }

    public function testAddDataSupportSetOrganizationName()
    {
        $organization = new Organization();
        $organization->setName('Org Name');
        $record = new ResultRecord(['organization' => 1]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->once())->method('find')->willReturn($organization);
        $this->listener->addOrganizationSupport($event);
        $this->assertSame($organization->getName(), $record->getValue('organization'));
    }

    public function testAddDataSupportMultipleOrganizations()
    {
        $organization = new Organization();
        $organization->setName('Org Name');
        $organization2 = new Organization();
        $organization2->setName('Org Name 2');

        $record = new ResultRecord(['organization' => 1]);
        $record2 = new ResultRecord(['organization' => 2]);
        $event = new OrmResultAfter($this->datagrid, [$record, $record2]);
        $this->repository->expects($this->exactly(2))->method('find')->willReturnMap(
            [
                [1, null, null, $organization],
                [2, null, null, $organization2],
            ]
        );
        $this->listener->addOrganizationSupport($event);

        $this->assertSame($organization->getName(), $record->getValue('organization'));
        $this->assertSame($organization2->getName(), $record2->getValue('organization'));
    }
}
