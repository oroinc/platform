<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Entity\Repository\AuditFieldRepository;
use Oro\Bundle\DataAuditBundle\EventListener\AuditGridDataListener;
use Oro\Bundle\DataAuditBundle\Model\FieldsTransformer;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class AuditGridDataListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var AuditGridDataListener */
    private $listener;

    /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $datagrid;

    /** @var AuditFieldRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var FieldsTransformer */
    private $fieldsTransformer;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repository = $this->createMock(AuditFieldRepository::class);
        $this->fieldsTransformer = new FieldsTransformer();
        $this->doctrineHelper->expects($this->any())->method('getEntityRepository')->willReturn($this->repository);

        $this->listener = new AuditGridDataListener($this->doctrineHelper, $this->fieldsTransformer);

        $this->datagrid = $this->createMock(DatagridInterface::class);
    }

    public function testAddDataSupportNoData()
    {
        $event = new OrmResultAfter($this->datagrid, []);
        $this->listener->addDataSupport($event);
    }

    public function testAddDataSupportNoFields()
    {
        $record = new ResultRecord(['id' => 1]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->once())->method('getVisibleFieldsByAuditIds')->willReturn([]);
        $this->listener->addDataSupport($event);
        $this->assertNull($record->getValue('data'));
    }

    public function testAddDataSupportNoIds()
    {
        $record = new ResultRecord([]);
        $event = new OrmResultAfter($this->datagrid, [$record]);
        $this->repository->expects($this->once())->method('getVisibleFieldsByAuditIds')->willReturn([]);
        $this->listener->addDataSupport($event);
        $this->assertNull($record->getValue('data'));
    }

    public function testAddDataSupportWithAData()
    {
        $record1 = new ResultRecord(['id' => 23]);
        $record2 = new ResultRecord(['id' => 24]);
        $record3 = new ResultRecord(['id' => 25]);
        $record4 = new ResultRecord(['id' => 26]);

        $event = new OrmResultAfter($this->datagrid, [$record1, $record2, $record3, $record4]);

        $oldDate = new \DateTime();
        $newDate = new \DateTime();

        $auditFieldWithTranslationDomain = new AuditField('field5', 'string', 'new_translatable', 'old_translatable');
        $auditFieldWithTranslationDomain->setTranslationDomain('message');

        $this->repository->expects($this->once())->method('getVisibleFieldsByAuditIds')->willReturn(
            [
                23 => [
                    new AuditField('field', 'integer', 1, 0),
                    new AuditField('field2', 'string', 'new_', '_old'),
                ],
                24 => [
                    new AuditField('field3', 'date', $newDate, $oldDate),
                    new AuditField('field4', 'datetime', $newDate, $oldDate),
                ],
                25 => [
                    $auditFieldWithTranslationDomain,
                ],
            ]
        );
        $this->listener->addDataSupport($event);
        $this->assertSame(
            [
                'field' => ['old' => 0, 'new' => 1],
                'field2' => ['old' => '_old', 'new' => 'new_'],
            ],
            $record1->getValue('data')
        );
        $this->assertSame(
            [
                'field3' => [
                    'old' => ['value' => $oldDate, 'type' => 'date'],
                    'new' => ['value' => $newDate, 'type' => 'date'],
                ],
                'field4' => [
                    'old' => ['value' => $oldDate, 'type' => 'datetime'],
                    'new' => ['value' => $newDate, 'type' => 'datetime'],
                ],
            ],
            $record2->getValue('data')
        );
        $this->assertSame(
            [
                'field5' => [
                    'old' => 'old_translatable',
                    'new' => 'new_translatable',
                    'translationDomain' => 'message',
                ],
            ],
            $record3->getValue('data')
        );
        $this->assertNull($record4->getValue('data'));
    }
}
