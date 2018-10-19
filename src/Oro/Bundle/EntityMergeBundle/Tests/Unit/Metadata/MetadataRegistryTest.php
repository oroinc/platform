<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;

class MetadataRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $metadataBuilder;

    /**
     * @var MetadataRegistry
     */
    protected $metadataRegistry;

    protected function setUp()
    {
        $this->metadataBuilder = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\MetadataBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataRegistry = new MetadataRegistry($this->metadataBuilder);
    }

    public function testGetEntityMetadata()
    {
        $className = 'TestEntity';

        $expectedResult = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataBuilder->expects($this->once())
            ->method('createEntityMetadataByClass')
            ->with($className)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->metadataRegistry->getEntityMetadata($className));
        $this->assertEquals($expectedResult, $this->metadataRegistry->getEntityMetadata($className));
    }
}
