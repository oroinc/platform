<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\EventListener\MergeField;

use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\MergeField\CascadeRemoveAssociationListener;
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAfterMergeField()
    {
        $fooEntity = new EntityStub(1);
        $barEntity = new EntityStub(2);
        $bazEntity = new EntityStub(2);

        $entities = array($fooEntity, $barEntity, $bazEntity);
        $entitiesToClear = array($barEntity, $bazEntity);

        $masterEntity = $fooEntity;

        $equalValueMap = array(
            array($masterEntity, $fooEntity, true),
            array($masterEntity, $barEntity, false),
            array($masterEntity, $bazEntity, false),
        );

        $this->doctrineHelper->expects($this->exactly(count($equalValueMap)))
            ->method('isEntityEqual')
            ->will($this->returnValueMap($equalValueMap));

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
            ->will($this->returnValue($entities));

        $entityData
            ->expects($this->once())
            ->method('getMasterEntity')
            ->will($this->returnValue($masterEntity));

        $fieldMetadata
            ->expects($this->once())
            ->method('hasDoctrineMetadata')
            ->will($this->returnValue(true));

        $fieldMetadata
            ->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->will($this->returnValue(true));

        $fieldMetadata
            ->expects($this->once())
            ->method('isCollection')
            ->will($this->returnValue(false));

        $doctrineMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineMetadata
            ->expects($this->any())
            ->method('isAssociation')
            ->will($this->returnValue(true));

        $doctrineMetadata
            ->expects($this->once())
            ->method('get')
            ->with('cascade')
            ->will($this->returnValue(array('remove')));

        $fieldMetadata
            ->expects($this->atLeastOnce())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        foreach ($entitiesToClear as $index => $entity) {
            $this->accessor
                ->expects($this->at($index))
                ->method('setValue')
                ->with($entity, $fieldMetadata, null);
        }

        $event = new FieldDataEvent($fieldData);

        $this->listener->afterMergeField($event);
    }
}
