<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\MergeModesListener;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MergeModesListenerTest extends TestCase
{
    private EntityMetadata&MockObject $entityMetadata;
    private FieldMetadata&MockObject $fieldMetadata;
    private MergeModesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityMetadata = $this->createMock(EntityMetadata::class);
        $this->fieldMetadata = $this->createMock(FieldMetadata::class);

        $this->entityMetadata->expects($this->any())
            ->method('getFieldsMetadata')
            ->willReturn([$this->fieldMetadata]);

        $this->listener = new MergeModesListener();
    }

    public function testOnCreateMetadata(): void
    {
        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->fieldMetadata->expects($this->atLeastOnce())
            ->method('addMergeMode')
            ->willReturn([$this->fieldMetadata]);

        $this->listener->onCreateMetadata($event);
    }

    public function testOnCreateMetadataForCollection(): void
    {
        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->fieldMetadata->expects($this->atLeastOnce())
            ->method('addMergeMode')
            ->willReturn([$this->fieldMetadata]);

        $this->fieldMetadata->expects($this->atLeastOnce())
            ->method('isCollection')
            ->willReturn(true);

        $this->listener->onCreateMetadata($event);
    }
}
