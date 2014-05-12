<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\DefaultLabelListener;

class DefaultLabelListenerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Namespace\\Entity';

    /**
     * @var DefaultLabelListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityConfigHelper;

    protected function setUp()
    {
        $this->entityConfigHelper = $this
            ->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\EventListener\\Metadata\\EntityConfigHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new DefaultLabelListener($this->entityConfigHelper);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnCreateMetadata()
    {
        $entityMetadata = $this->createEntityMetadata();
        $entityMetadata->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(self::ENTITY_CLASS));

        $entityMetadata->expects($this->once())
            ->method('has')
            ->with('label')
            ->will($this->returnValue(false));

        $entityConfig = $this->createEntityConfig();

        $this->entityConfigHelper->expects($this->at(0))
            ->method('getConfig')
            ->with('entity', self::ENTITY_CLASS, null)
            ->will($this->returnValue($entityConfig));

        $expectedEntityPluralLabel = 'entity_plural_label';
        $entityConfig->expects($this->once())
            ->method('get')
            ->with('plural_label')
            ->will($this->returnValue($expectedEntityPluralLabel));

        $entityMetadata->expects($this->once())
            ->method('set')
            ->with('label', $expectedEntityPluralLabel);

        $fooField = $this->createFieldMetadata();
        $barField = $this->createFieldMetadata();
        $bazField = $this->createFieldMetadata();

        $entityMetadata->expects($this->once())
            ->method('getFieldsMetadata')
            ->will($this->returnValue(array($fooField, $barField, $bazField)));

        // Field with label
        $fooField->expects($this->once())
            ->method('has')
            ->with('label')
            ->will($this->returnValue(true));

        // Field not defined by source entity and collection
        $barField->expects($this->once())
            ->method('has')
            ->with('label')
            ->will($this->returnValue(false));

        $barExpectedSourceClassName = 'Bar\\Entity';
        $barField->expects($this->once())
            ->method('getSourceClassName')
            ->will($this->returnValue($barExpectedSourceClassName));

        $barExpectedSourceFieldName = 'bar_source_field_name';
        $barField->expects($this->once())
            ->method('getSourceFieldName')
            ->will($this->returnValue($barExpectedSourceFieldName));

        $barField->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->will($this->returnValue(false));

        $barField->expects($this->once())
            ->method('isCollection')
            ->will($this->returnValue(true));

        $barFieldEntityConfig = $this->createEntityConfig();

        $this->entityConfigHelper->expects($this->at(1))
            ->method('getConfig')
            ->with('entity', $barExpectedSourceClassName, null)
            ->will($this->returnValue($barFieldEntityConfig));

        $barExpectedFieldLabel = 'bar_expected_field_label';

        $barFieldEntityConfig->expects($this->once())
            ->method('get')
            ->with('plural_label')
            ->will($this->returnValue($barExpectedFieldLabel));

        $barField->expects($this->once())
            ->method('set')
            ->with('label', $barExpectedFieldLabel);

        // Field defined by source entity
        $bazField->expects($this->once())
            ->method('has')
            ->with('label')
            ->will($this->returnValue(false));

        $bazExpectedSourceClassName = 'Baz\\Entity';
        $bazField->expects($this->once())
            ->method('getSourceClassName')
            ->will($this->returnValue($bazExpectedSourceClassName));

        $bazExpectedSourceFieldName = 'baz_source_field_name';
        $bazField->expects($this->once())
            ->method('getSourceFieldName')
            ->will($this->returnValue($bazExpectedSourceFieldName));

        $bazField->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->will($this->returnValue(true));

        $bazFieldEntityConfig = $this->createEntityConfig();

        $this->entityConfigHelper->expects($this->at(2))
            ->method('getConfig')
            ->with('entity', $bazExpectedSourceClassName, $bazExpectedSourceFieldName)
            ->will($this->returnValue($bazFieldEntityConfig));

        $bazExpectedFieldLabel = 'bar_expected_field_label';

        $bazFieldEntityConfig->expects($this->once())
            ->method('get')
            ->with('label')
            ->will($this->returnValue($bazExpectedFieldLabel));

        $bazField->expects($this->once())
            ->method('set')
            ->with('label', $bazExpectedFieldLabel);

        $this->listener->onCreateMetadata($this->createEntityMetadataEvent($entityMetadata));
    }

    protected function createEntityConfig()
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityConfigBundle\\Config\\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createEntityMetadata()
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFieldMetadata()
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createEntityMetadataEvent(EntityMetadata $entityMetadata)
    {
        $result = $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Event\\EntityMetadataEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $result->expects($this->atLeastOnce())
            ->method('getEntityMetadata')
            ->will($this->returnValue($entityMetadata));

        return $result;
    }
}
