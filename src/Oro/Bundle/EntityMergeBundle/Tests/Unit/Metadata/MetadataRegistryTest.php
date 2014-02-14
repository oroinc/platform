<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;

class MetadataRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataFactory;

    /**
     * @var MetadataRegistry
     */
    protected $metadataRegistry;

    protected function setUp()
    {
        $this->metadataFactory = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataRegistry = new MetadataRegistry($this->metadataFactory);
    }

    public function testGetEntityMetadata()
    {
        $className = 'TestEntity';

        $expectedResult = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataFactory->expects($this->once())
            ->method('createEntityMetadata')
            ->with($className)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->metadataRegistry->getEntityMetadata($className));
        $this->assertEquals($expectedResult, $this->metadataRegistry->getEntityMetadata($className));
    }
}
