<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use PHPUnit\Framework\TestCase;

class DoctrineMetadataTest extends TestCase
{
    private const CLASS_NAME = 'Namespace\Entity';

    private DoctrineMetadata $doctrineMetadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineMetadata = new DoctrineMetadata();
    }

    public function testGetFieldName(): void
    {
        $expectedFieldName = 'test';
        $this->doctrineMetadata->set('fieldName', $expectedFieldName);
        $this->assertEquals($expectedFieldName, $this->doctrineMetadata->getFieldName());
    }

    public function testGetFieldNameFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "fieldName" not exists');

        $this->doctrineMetadata->getFieldName();
    }

    public function testIsField(): void
    {
        $this->assertTrue($this->doctrineMetadata->isField());

        $this->doctrineMetadata->set('targetEntity', self::CLASS_NAME);
        $this->assertFalse($this->doctrineMetadata->isField());

        $this->doctrineMetadata->set('joinColumns', []);
        $this->assertFalse($this->doctrineMetadata->isField());
    }

    public function testIsAssociation(): void
    {
        $this->assertFalse($this->doctrineMetadata->isAssociation());

        $this->doctrineMetadata->set('targetEntity', self::CLASS_NAME);
        $this->assertTrue($this->doctrineMetadata->isAssociation());

        $this->doctrineMetadata->set('joinColumns', []);
        $this->assertTrue($this->doctrineMetadata->isAssociation());
    }

    public function testIsOneToOne(): void
    {
        $this->assertFalse($this->doctrineMetadata->isOneToOne());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::ONE_TO_ONE);
        $this->assertTrue($this->doctrineMetadata->isOneToOne());
    }

    public function testIsOneToMany(): void
    {
        $this->assertFalse($this->doctrineMetadata->isOneToMany());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::ONE_TO_MANY);
        $this->assertTrue($this->doctrineMetadata->isOneToMany());
    }

    public function testIsManyToMany(): void
    {
        $this->assertFalse($this->doctrineMetadata->isManyToMany());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::MANY_TO_MANY);
        $this->assertTrue($this->doctrineMetadata->isManyToMany());
    }

    public function testIsManyToOne(): void
    {
        $this->assertFalse($this->doctrineMetadata->isManyToOne());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::MANY_TO_ONE);
        $this->assertTrue($this->doctrineMetadata->isManyToOne());
    }

    public function testIsTypeEqual(): void
    {
        $expectedType = ClassMetadataInfo::ONE_TO_MANY;
        $this->doctrineMetadata->set('type', $expectedType);
        $this->assertTrue($this->doctrineMetadata->isTypeEqual($expectedType));
        $this->assertFalse($this->doctrineMetadata->isTypeEqual(ClassMetadataInfo::ONE_TO_ONE));
    }

    public function testOrphanRemoval(): void
    {
        $this->assertFalse($this->doctrineMetadata->isOrphanRemoval());

        $this->doctrineMetadata->set('orphanRemoval', true);
        $this->assertTrue($this->doctrineMetadata->isOrphanRemoval());
    }
}
