<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\MergeModesListener;

class MergeModesListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MergeModesListener
     */
    protected $listener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityMetadata;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldMetadata;

    protected function setUp(): void
    {
        $this->entityMetadata = $this
            ->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldMetadata = $this
            ->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadata
            ->expects($this->any())
            ->method('getFieldsMetadata')
            ->will($this->returnValue([$this->fieldMetadata]));

        $this->listener = new MergeModesListener();
    }

    public function testOnCreateMetadata()
    {
        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->fieldMetadata
            ->expects($this->atLeastOnce())
            ->method('addMergeMode')
            ->will($this->returnValue([$this->fieldMetadata]));

        $this->listener->onCreateMetadata($event);
    }

    public function testOnCreateMetadataForCollection()
    {
        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->fieldMetadata
            ->expects($this->atLeastOnce())
            ->method('addMergeMode')
            ->will($this->returnValue([$this->fieldMetadata]));

        $this->fieldMetadata
            ->expects($this->atLeastOnce())
            ->method('isCollection')
            ->will($this->returnValue(true));

        $this->listener->onCreateMetadata($event);
    }
}
