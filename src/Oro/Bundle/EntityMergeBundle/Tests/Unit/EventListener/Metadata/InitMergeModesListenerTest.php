<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\InitMergeModesListener;

class InitMergeModesListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InitMergeModesListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldMetadata;

    protected function setUp()
    {
        $this->entityMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadata
            ->expects($this->any())
            ->method('getFieldsMetadata')
            ->will($this->returnValue([$this->fieldMetadata]));

        $this->listener = new InitMergeModesListener();
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
