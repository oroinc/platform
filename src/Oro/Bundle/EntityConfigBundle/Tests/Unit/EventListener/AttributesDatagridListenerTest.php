<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributesDatagridListener;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributesDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject*/
    private $doctrineHelper;

    /** @var AttributesDatagridListener */
    private $listener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new AttributesDatagridListener($this->doctrineHelper);
    }

    public function testOnResultAfter()
    {
        $resultRecord1 = new ResultRecord(['id' => 1]);
        $resultRecord2 = new ResultRecord(['id' => 2]);
        $resultRecord5 = new ResultRecord(['id' => 5]);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$resultRecord1, $resultRecord2, $resultRecord5]
        );

        $repository = $this->getMockBuilder(AttributeGroupRelationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $family1 = $this->getEntity(AttributeFamily::class, ['code' => 'family1']);
        $family2 = $this->getEntity(AttributeFamily::class, ['code' => 'family2']);
        $families = [
            1 => [],
            2 => [$family1, $family2],
            5 => [$family2]
        ];

        $repository
            ->expects($this->once())
            ->method('getFamiliesLabelsByAttributeIds')
            ->with([1, 2, 5])
            ->willReturn($families);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeGroupRelation::class)
            ->willReturn($repository);

        $this->listener->onResultAfter($event);

        $resultRecord1->addData(['attributeFamilies' => []]);
        $resultRecord2->addData(['attributeFamilies' => [$family1, $family2]]);
        $resultRecord5->addData(['attributeFamilies' => [$family2]]);
        $expectedRecords = [
            $resultRecord1,
            $resultRecord2,
            $resultRecord5
        ];

        $this->assertEquals($expectedRecords, $event->getRecords());
    }
}
