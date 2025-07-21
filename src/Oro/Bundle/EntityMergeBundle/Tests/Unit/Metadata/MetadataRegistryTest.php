<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataBuilder;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetadataRegistryTest extends TestCase
{
    private MetadataBuilder&MockObject $metadataBuilder;
    private MetadataRegistry $metadataRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadataBuilder = $this->createMock(MetadataBuilder::class);

        $this->metadataRegistry = new MetadataRegistry($this->metadataBuilder);
    }

    public function testGetEntityMetadata(): void
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
