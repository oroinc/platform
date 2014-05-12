<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\EntityConfigListener;

class EntityConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityConfigListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    protected function setUp()
    {
        $this->helper = $this
            ->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\EventListener\\Metadata\\EntityConfigHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new EntityConfigListener($this->helper);
    }

    public function testOnCreateMetadata()
    {
        $className = 'Foo\\Entity';

        $entityMetadata = $this->createEntityMetadata();

        $event = $this->createEntityMetadataEvent();

        $event->expects($this->once())
            ->method('getEntityMetadata')
            ->will($this->returnValue($entityMetadata));

        // Check entity metadata
        $entityMetadata->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue($className));

        $entityMergeOptions = array('enable' => true);
        $entityMergeConfig = $this->createConfig($entityMergeOptions);

        $this->helper->expects($this->at(0))
            ->method('getConfig')
            ->with(EntityConfigListener::CONFIG_MERGE_SCOPE, $className, null)
            ->will($this->returnValue($entityMergeConfig));

        $entityMetadata->expects($this->once())
            ->method('merge')
            ->with($entityMergeOptions);

        $fooFieldMetadata = $this->createFieldMetadata();
        $barFieldMetadata = $this->createFieldMetadata();
        $entityMetadata->expects($this->once())
            ->method('getFieldsMetadata')
            ->will($this->returnValue(array($fooFieldMetadata, $barFieldMetadata)));

        // Check field defined by source entity
        $this->helper->expects($this->at(1))
            ->method('prepareFieldMetadataPropertyPath')
            ->with($fooFieldMetadata);

        $fooEntityMergeOptions = array(
            'display' => false,
            'inverse_display' => true,
            'property_path' => 'test',
        );
        $expectedFooEntityMergeOptions = array(
            'display' => false,
            'property_path' => 'test',
        );
        $fooEntityMergeConfig = $this->createConfig($fooEntityMergeOptions);

        $this->helper->expects($this->at(2))
            ->method('getConfigByFieldMetadata')
            ->with(EntityConfigListener::CONFIG_MERGE_SCOPE, $fooFieldMetadata)
            ->will($this->returnValue($fooEntityMergeConfig));

        $fooFieldMetadata->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->will($this->returnValue(true));

        $fooFieldMetadata->expects($this->once())
            ->method('merge')
            ->with($expectedFooEntityMergeOptions);

        // Check field defined by inverse entity
        $this->helper->expects($this->at(3))
            ->method('prepareFieldMetadataPropertyPath')
            ->with($barFieldMetadata);

        $barEntityMergeOptions = array(
            'display' => false,
            'inverse_display' => true,
            'property_path' => 'test',
        );
        $expectedBarEntityMergeOptions = array(
            'display' => true,
            'property_path' => 'test',
        );
        $barEntityMergeConfig = $this->createConfig($barEntityMergeOptions);

        $this->helper->expects($this->at(4))
            ->method('getConfigByFieldMetadata')
            ->with(EntityConfigListener::CONFIG_MERGE_SCOPE, $barFieldMetadata)
            ->will($this->returnValue($barEntityMergeConfig));

        $barFieldMetadata->expects($this->once())
            ->method('isDefinedBySourceEntity')
            ->will($this->returnValue(false));

        $barFieldMetadata->expects($this->once())
            ->method('merge')
            ->with($expectedBarEntityMergeOptions);

        $this->listener->onCreateMetadata($event);
    }

    protected function createConfig(array $arrayOptions)
    {
        $result = $this->getMock('Oro\\Bundle\\EntityConfigBundle\\Config\\ConfigInterface');
        $result->expects($this->once())
            ->method('all')
            ->will($this->returnValue($arrayOptions));
        return $result;
    }

    protected function createEntityMetadataEvent()
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Event\\EntityMetadataEvent')
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
}
