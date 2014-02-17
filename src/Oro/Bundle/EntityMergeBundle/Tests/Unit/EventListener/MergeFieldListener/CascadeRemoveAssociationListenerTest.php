<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\EventListener\MergeFieldListener;

use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\MergeFieldListener\CascadeRemoveAssociationListener;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class CascadeRemoveAssociationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CascadeRemoveAssociationListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $accessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->accessor = $this
            ->getMock('Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface');

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CascadeRemoveAssociationListener($this->accessor, $this->doctrineHelper);
    }

    public function testAfterMergeField()
    {
        $fieldData = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->disableOriginalConstructor()
            ->getMock();

        $fieldMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $fieldData
            ->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadata));

        $fieldData
            ->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(MergeModes::REPLACE));

        $entityData = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();

        $fieldData
            ->expects($this->once())
            ->method('getEntityData')
            ->will($this->returnValue($entityData));

        $entityData
            ->expects($this->once())
            ->method('getEntities')
            ->will($this->returnValue([new \stdClass(), new \stdClass()]));

        $entityData
            ->expects($this->once())
            ->method('getMasterEntity')
            ->will($this->returnValue(new EntityStub()));

        $fieldMetadata
            ->expects($this->once())
            ->method('hasDoctrineMetadata')
            ->will($this->returnValue(true));

        $doctrineMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineMetadata
            ->expects($this->any())
            ->method('isMappedBySourceEntity')
            ->will($this->returnValue(true));

        $doctrineMetadata
            ->expects($this->any())
            ->method('isAssociation')
            ->will($this->returnValue(true));

        $doctrineMetadata
            ->expects($this->any())
            ->method('isCollection')
            ->will($this->returnValue(false));

        $doctrineMetadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue('remove'));

        $fieldMetadata
            ->expects($this->any())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $this->accessor
            ->expects($this->atLeastOnce())
            ->method('setValue');

        $event = new FieldDataEvent($fieldData);

        $this->listener->afterMergeField($event);
    }
}
