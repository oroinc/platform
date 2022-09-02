<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\MergeModesListener;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class MergeModesListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $entityMetadata;

    /** @var FieldMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldMetadata;

    /** @var MergeModesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->entityMetadata = $this->createMock(EntityMetadata::class);
        $this->fieldMetadata = $this->createMock(FieldMetadata::class);

        $this->entityMetadata->expects($this->any())
            ->method('getFieldsMetadata')
            ->willReturn([$this->fieldMetadata]);

        $this->listener = new MergeModesListener();
    }

    public function testOnCreateMetadata()
    {
        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->fieldMetadata->expects($this->atLeastOnce())
            ->method('addMergeMode')
            ->willReturn([$this->fieldMetadata]);

        $this->listener->onCreateMetadata($event);
    }

    public function testOnCreateMetadataForCollection()
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
