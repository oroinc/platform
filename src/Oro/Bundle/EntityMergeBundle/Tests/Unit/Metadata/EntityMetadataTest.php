<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class EntityMetadataTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineMetadata;

    /** @var EntityMetadata */
    private $metadata;

    protected function setUp(): void
    {
        $this->doctrineMetadata = $this->createMock(DoctrineMetadata::class);

        $this->metadata = new EntityMetadata(['foo' => 'bar'], $this->doctrineMetadata);
    }

    public function testAddFieldMetadata()
    {
        $this->assertEquals([], $this->metadata->getFieldsMetadata());
    }

    public function testGetDoctrineMetadata()
    {
        $this->assertEquals($this->doctrineMetadata, $this->metadata->getDoctrineMetadata());
    }

    public function testGetDoctrineMetadataFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Doctrine metadata is not configured.');

        $metadata = new EntityMetadata();
        $metadata->getDoctrineMetadata();
    }

    public function testFieldsMetadata()
    {
        $fieldName = 'test';
        $fieldMetadata = $this->createMock(FieldMetadata::class);
        $fieldMetadata->expects($this->any())
            ->method('getFieldName')
            ->willReturn($fieldName);

        $this->metadata->addFieldMetadata($fieldMetadata);

        $this->assertEquals([$fieldName => $fieldMetadata], $this->metadata->getFieldsMetadata());
    }

    public function testGetClassName()
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

    public function testGetClassNameFails()
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
