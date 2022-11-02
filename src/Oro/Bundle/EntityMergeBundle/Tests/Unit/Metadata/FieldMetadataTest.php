<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FieldMetadataTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $options;

    /** @var DoctrineMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineMetadata;

    /** @var EntityMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $entityMetadata;

    /** @var FieldMetadata */
    private $fieldMetadata;

    protected function setUp(): void
    {
        $this->options = ['foo' => 'bar'];
        $this->doctrineMetadata = $this->createMock(DoctrineMetadata::class);
        $this->entityMetadata = $this->createMock(EntityMetadata::class);

        $this->fieldMetadata = new FieldMetadata($this->options, $this->doctrineMetadata);
    }

    public function testGetEntityMetadata()
    {
        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->assertEquals($this->entityMetadata, $this->fieldMetadata->getEntityMetadata());
    }

    public function testGetEntityMetadataFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity metadata is not configured.');

        $this->fieldMetadata->getEntityMetadata();
    }

    public function testGetDoctrineMetadata()
    {
        $this->assertEquals($this->doctrineMetadata, $this->fieldMetadata->getDoctrineMetadata());
    }

    public function testGetDoctrineMetadataFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Doctrine metadata is not configured.');

        $metadata = new FieldMetadata();
        $metadata->getDoctrineMetadata();
    }

    public function testHasDoctrineMetadata()
    {
        $metadata = new FieldMetadata($this->options);
        $this->assertFalse($metadata->hasDoctrineMetadata());

        $metadata->setDoctrineMetadata($this->doctrineMetadata);
        $this->assertTrue($metadata->hasDoctrineMetadata());
    }

    public function testGetFieldName()
    {
        $fieldName = 'field';

        $this->fieldMetadata->set('field_name', $fieldName);

        $this->assertEquals($fieldName, $this->fieldMetadata->getFieldName());
    }

    public function testGetSourceFieldName()
    {
        $fieldName = 'field';
        $this->fieldMetadata->set('source_field_name', $fieldName);
        $this->assertEquals($fieldName, $this->fieldMetadata->getSourceFieldName());
    }

    public function testGetSourceFieldNameWhenOptionEmpty()
    {
        $fieldName = 'field';
        $this->fieldMetadata->set('field_name', $fieldName);
        $this->assertEquals($fieldName, $this->fieldMetadata->getSourceFieldName());
    }

    public function testGetSourceClassNameByEntityMetadata()
    {
        $className = 'Foo\\Entity';
        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);
        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);

        $this->assertEquals($className, $this->fieldMetadata->getSourceClassName());
    }

    public function testGetSourceClassNameByOption()
    {
        $className = 'Foo\\Entity';
        $this->fieldMetadata->set('source_class_name', $className);
        $this->assertEquals($className, $this->fieldMetadata->getSourceClassName());
    }

    public function testIsDefinedBySourceEntityTrue()
    {
        $className = 'Foo\\Entity';

        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->fieldMetadata->set('source_class_name', $className);

        $this->assertTrue($this->fieldMetadata->isDefinedBySourceEntity());
    }

    public function testIsDefinedBySourceEntityFalse()
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

    public function testGetFieldNameFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot get field name from merge field metadata.');

        $this->fieldMetadata->getFieldName();
    }

    public function testGetMergeMode()
    {
        $mergeModes = [MergeModes::REPLACE, MergeModes::UNITE];
        $this->assertNull($this->fieldMetadata->getMergeMode());

        $this->fieldMetadata->set('merge_modes', $mergeModes);
        $this->assertEquals(MergeModes::REPLACE, $this->fieldMetadata->getMergeMode());
    }

    public function testHasMergeMode()
    {
        $mergeModes = [MergeModes::REPLACE];
        $this->fieldMetadata->set('merge_modes', $mergeModes);

        $this->assertTrue($this->fieldMetadata->hasMergeMode(MergeModes::REPLACE));
        $this->assertFalse($this->fieldMetadata->hasMergeMode(MergeModes::UNITE));
    }

    public function testAddMergeMode()
    {
        $this->assertEquals([], $this->fieldMetadata->getMergeModes());
        $this->fieldMetadata->addMergeMode(MergeModes::REPLACE);
        $this->fieldMetadata->addMergeMode(MergeModes::REPLACE);
        $this->assertEquals([MergeModes::REPLACE], $this->fieldMetadata->getMergeModes());
        $this->fieldMetadata->addMergeMode(MergeModes::UNITE);
        $this->assertEquals([MergeModes::UNITE, MergeModes::REPLACE], $this->fieldMetadata->getMergeModes());
    }

    public function testIsCollectionFalseWhenNotHasDoctrineMetadata()
    {
        $metadata = new FieldMetadata();
        $metadata->set('is_collection', true);
        $this->assertTrue($metadata->isCollection());

        $metadata->set('is_collection', false);
        $this->assertFalse($metadata->isCollection());
    }

    public function testIsCollectionFalseWhenNotAssociation()
    {
        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->willReturn(false);
        $this->assertFalse($this->fieldMetadata->isCollection());
    }

    public function testIsCollectionTrueWhenManyToMany()
    {
        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->willReturn(true);

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToMany')
            ->willReturn(true);

        $this->assertTrue($this->fieldMetadata->isCollection());
    }

    public function testIsCollectionTrueWhenOneToManyDefinedBySourceEntity()
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

    public function testIsCollectionTrueWhenManyToOneDefinedBySourceEntity()
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

    public function testOneToManyRelationShouldBeCloned()
    {
        $fieldMetadata = new FieldMetadata([], new DoctrineMetadata([
            'type' => ClassMetadataInfo::ONE_TO_MANY,
            'orphanRemoval' => true,
            'targetEntity' => 'Foo\\Entity',
        ]));

        $this->assertTrue($fieldMetadata->shouldBeCloned());
    }
}
