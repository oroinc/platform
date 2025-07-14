<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FieldMetadataTest extends TestCase
{
    private array $options;
    private DoctrineMetadata&MockObject $doctrineMetadata;
    private EntityMetadata&MockObject $entityMetadata;
    private FieldMetadata $fieldMetadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->options = ['foo' => 'bar'];
        $this->doctrineMetadata = $this->createMock(DoctrineMetadata::class);
        $this->entityMetadata = $this->createMock(EntityMetadata::class);

        $this->fieldMetadata = new FieldMetadata($this->options, $this->doctrineMetadata);
    }

    public function testGetEntityMetadata(): void
    {
        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->assertEquals($this->entityMetadata, $this->fieldMetadata->getEntityMetadata());
    }

    public function testGetEntityMetadataFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity metadata is not configured.');

        $this->fieldMetadata->getEntityMetadata();
    }

    public function testGetDoctrineMetadata(): void
    {
        $this->assertEquals($this->doctrineMetadata, $this->fieldMetadata->getDoctrineMetadata());
    }

    public function testGetDoctrineMetadataFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Doctrine metadata is not configured.');

        $metadata = new FieldMetadata();
        $metadata->getDoctrineMetadata();
    }

    public function testHasDoctrineMetadata(): void
    {
        $metadata = new FieldMetadata($this->options);
        $this->assertFalse($metadata->hasDoctrineMetadata());

        $metadata->setDoctrineMetadata($this->doctrineMetadata);
        $this->assertTrue($metadata->hasDoctrineMetadata());
    }

    public function testGetFieldName(): void
    {
        $fieldName = 'field';

        $this->fieldMetadata->set('field_name', $fieldName);

        $this->assertEquals($fieldName, $this->fieldMetadata->getFieldName());
    }

    public function testGetSourceFieldName(): void
    {
        $fieldName = 'field';
        $this->fieldMetadata->set('source_field_name', $fieldName);
        $this->assertEquals($fieldName, $this->fieldMetadata->getSourceFieldName());
    }

    public function testGetSourceFieldNameWhenOptionEmpty(): void
    {
        $fieldName = 'field';
        $this->fieldMetadata->set('field_name', $fieldName);
        $this->assertEquals($fieldName, $this->fieldMetadata->getSourceFieldName());
    }

    public function testGetSourceClassNameByEntityMetadata(): void
    {
        $className = 'Foo\\Entity';
        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);
        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);

        $this->assertEquals($className, $this->fieldMetadata->getSourceClassName());
    }

    public function testGetSourceClassNameByOption(): void
    {
        $className = 'Foo\\Entity';
        $this->fieldMetadata->set('source_class_name', $className);
        $this->assertEquals($className, $this->fieldMetadata->getSourceClassName());
    }

    public function testIsDefinedBySourceEntityTrue(): void
    {
        $className = 'Foo\\Entity';

        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->fieldMetadata->set('source_class_name', $className);

        $this->assertTrue($this->fieldMetadata->isDefinedBySourceEntity());
    }

    public function testIsDefinedBySourceEntityFalse(): void
    {
        $className = 'Foo\\Entity';
        $sourceClassName = 'Bar\\Entity';

        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->fieldMetadata->set('source_class_name', $sourceClassName);

        $this->assertFalse($this->fieldMetadata->isDefinedBySourceEntity());
    }

    public function testGetFieldNameFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot get field name from merge field metadata.');

        $this->fieldMetadata->getFieldName();
    }

    public function testGetMergeMode(): void
    {
        $mergeModes = [MergeModes::REPLACE, MergeModes::UNITE];
        $this->assertNull($this->fieldMetadata->getMergeMode());

        $this->fieldMetadata->set('merge_modes', $mergeModes);
        $this->assertEquals(MergeModes::REPLACE, $this->fieldMetadata->getMergeMode());
    }

    public function testHasMergeMode(): void
    {
        $mergeModes = [MergeModes::REPLACE];
        $this->fieldMetadata->set('merge_modes', $mergeModes);

        $this->assertTrue($this->fieldMetadata->hasMergeMode(MergeModes::REPLACE));
        $this->assertFalse($this->fieldMetadata->hasMergeMode(MergeModes::UNITE));
    }

    public function testAddMergeMode(): void
    {
        $this->assertEquals([], $this->fieldMetadata->getMergeModes());
        $this->fieldMetadata->addMergeMode(MergeModes::REPLACE);
        $this->fieldMetadata->addMergeMode(MergeModes::REPLACE);
        $this->assertEquals([MergeModes::REPLACE], $this->fieldMetadata->getMergeModes());
        $this->fieldMetadata->addMergeMode(MergeModes::UNITE);
        $this->assertEquals([MergeModes::UNITE, MergeModes::REPLACE], $this->fieldMetadata->getMergeModes());
    }

    public function testIsCollectionFalseWhenNotHasDoctrineMetadata(): void
    {
        $metadata = new FieldMetadata();
        $metadata->set('is_collection', true);
        $this->assertTrue($metadata->isCollection());

        $metadata->set('is_collection', false);
        $this->assertFalse($metadata->isCollection());
    }

    public function testIsCollectionFalseWhenNotAssociation(): void
    {
        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->willReturn(false);
        $this->assertFalse($this->fieldMetadata->isCollection());
    }

    public function testIsCollectionTrueWhenManyToMany(): void
    {
        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->willReturn(true);

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToMany')
            ->willReturn(true);

        $this->assertTrue($this->fieldMetadata->isCollection());
    }

    public function testIsCollectionTrueWhenOneToManyDefinedBySourceEntity(): void
    {
        $className = 'Foo\\Entity';

        $this->entityMetadata->expects($this->exactly(2))
            ->method('getClassName')
            ->willReturn($className);

        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);

        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->willReturn(true);

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToMany')
            ->willReturn(false);

        $this->doctrineMetadata->expects($this->once())
            ->method('isOneToMany')
            ->willReturn(true);

        $this->assertTrue($this->fieldMetadata->isCollection());
    }

    public function testIsCollectionTrueWhenManyToOneDefinedBySourceEntity(): void
    {
        $className = 'Foo\\Entity';
        $sourceClassName = 'Bar\\Entity';

        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->fieldMetadata->set('source_class_name', $sourceClassName);

        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->willReturn(true);

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToMany')
            ->willReturn(false);

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToOne')
            ->willReturn(true);

        $this->assertTrue($this->fieldMetadata->isCollection());
    }

    public function testOneToManyRelationShouldBeCloned(): void
    {
        $fieldMetadata = new FieldMetadata([], new DoctrineMetadata([
            'type' => ClassMetadataInfo::ONE_TO_MANY,
            'orphanRemoval' => true,
            'targetEntity' => 'Foo\\Entity',
        ]));

        $this->assertTrue($fieldMetadata->shouldBeCloned());
    }
}
