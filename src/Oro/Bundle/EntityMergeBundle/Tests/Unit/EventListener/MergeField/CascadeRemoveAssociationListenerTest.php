<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\MergeField;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\MergeField\CascadeRemoveAssociationListener;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class CascadeRemoveAssociationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $accessor;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var CascadeRemoveAssociationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->accessor = $this->createMock(AccessorInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new CascadeRemoveAssociationListener($this->accessor, $this->doctrineHelper);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAfterMergeField()
    {
        $fooEntity = new EntityStub(1);
        $barEntity = new EntityStub(2);
        $bazEntity = new EntityStub(2);

        $masterEntity = $fooEntity;

        $this->doctrineHelper->expects($this->exactly(3))
            ->method('isEntityEqual')
            ->willReturnMap([
                [$masterEntity, $fooEntity, true],
                [$masterEntity, $barEntity, false],
                [$masterEntity, $bazEntity, false],
            ]);

        $entityData = $this->createMock(EntityData::class);
        $fieldData = $this->createMock(FieldData::class);
        $fieldMetadata = $this->createMock(FieldMetadata::class);
        $doctrineMetadata = $this->createMock(DoctrineMetadata::class);

        $fieldData->expects($this->once())
            ->method('getEntityData')
            ->willReturn($entityData);
        $fieldData->expects($this->once())
            ->method('getMetadata')
            ->willReturn($fieldMetadata);
        $fieldData->expects($this->once())
            ->method('getMode')
            ->willReturn(MergeModes::REPLACE);

        $entityData->expects($this->once())
            ->method('getEntities')
            ->willReturn([$fooEntity, $barEntity, $bazEntity]);
        $entityData->expects($this->once())
            ->method('getMasterEntity')
            ->willReturn($masterEntity);

        $fieldMetadata->expects($this->once())
            ->method('hasDoctrineMetadata')
            ->willReturn(true);
        $fieldMetadata->expects($this->atLeastOnce())
            ->method('getDoctrineMetadata')
            ->willReturn($doctrineMetadata);
        $fieldMetadata->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->willReturn(true);
        $fieldMetadata->expects($this->once())
            ->method('isCollection')
            ->willReturn(false);

        $doctrineMetadata->expects($this->any())
            ->method('isAssociation')
            ->willReturn(true);
        $doctrineMetadata->expects($this->once())
            ->method('get')
            ->with('cascade')
            ->willReturn(['remove']);

        $this->accessor->expects($this->exactly(2))
            ->method('setValue')
            ->withConsecutive(
                [$this->identicalTo($barEntity), $this->identicalTo($fieldMetadata), $this->isNull()],
                [$this->identicalTo($bazEntity), $this->identicalTo($fieldMetadata), $this->isNull()]
            );

        $this->listener->afterMergeField(new FieldDataEvent($fieldData));
    }
}
