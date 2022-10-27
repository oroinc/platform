<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataBuilder;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;

class MetadataRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var MetadataBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataBuilder;

    /** @var MetadataRegistry */
    private $metadataRegistry;

    protected function setUp(): void
    {
        $this->metadataBuilder = $this->createMock(MetadataBuilder::class);

        $this->metadataRegistry = new MetadataRegistry($this->metadataBuilder);
    }

    public function testGetEntityMetadata()
    {
        $className = 'TestEntity';

        $expectedResult = $this->createMock(EntityMetadata::class);

        $this->metadataBuilder->expects($this->once())
            ->method('createEntityMetadataByClass')
            ->with($className)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->metadataRegistry->getEntityMetadata($className));
        $this->assertEquals($expectedResult, $this->metadataRegistry->getEntityMetadata($className));
    }
}
