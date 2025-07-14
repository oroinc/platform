<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityMetadataTest extends TestCase
{
    private DoctrineMetadata&MockObject $doctrineMetadata;
    private EntityMetadata $metadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineMetadata = $this->createMock(DoctrineMetadata::class);

        $this->metadata = new EntityMetadata(['foo' => 'bar'], $this->doctrineMetadata);
    }

    public function testAddFieldMetadata(): void
    {
        $this->assertEquals([], $this->metadata->getFieldsMetadata());
    }

    public function testGetDoctrineMetadata(): void
    {
        $this->assertEquals($this->doctrineMetadata, $this->metadata->getDoctrineMetadata());
    }

    public function testGetDoctrineMetadataFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Doctrine metadata is not configured.');

        $metadata = new EntityMetadata();
        $metadata->getDoctrineMetadata();
    }

    public function testFieldsMetadata(): void
    {
        $fieldName = 'test';
        $fieldMetadata = $this->createMock(FieldMetadata::class);
        $fieldMetadata->expects($this->any())
            ->method('getFieldName')
            ->willReturn($fieldName);

        $this->metadata->addFieldMetadata($fieldMetadata);

        $this->assertEquals([$fieldName => $fieldMetadata], $this->metadata->getFieldsMetadata());
    }

    public function testGetClassName(): void
    {
        $className = 'TestEntity';

        $this->doctrineMetadata->expects($this->once())
            ->method('has')
            ->with('name')
            ->willReturn(true);

        $this->doctrineMetadata->expects($this->once())
            ->method('get')
            ->with('name')
            ->willReturn($className);

        $this->assertEquals($className, $this->metadata->getClassName());
    }

    public function testGetClassNameFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot get class name from merge entity metadata.');

        $this->doctrineMetadata->expects($this->once())
            ->method('has')
            ->with('name')
            ->willReturn(false);

        $this->metadata->getClassName();
    }
}
