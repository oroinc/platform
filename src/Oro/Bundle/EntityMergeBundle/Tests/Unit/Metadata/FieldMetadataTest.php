<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMetadata;

    /**
     * @var FieldMetadata
     */
    protected $fieldMetadata;

    protected function setUp()
    {
        $this->options = array('foo' => 'bar');
        $this->doctrineMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()->getMock();
        $this->entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()->getMock();
        $this->fieldMetadata = new FieldMetadata($this->options, $this->doctrineMetadata);
    }

    public function testGetEntityMetadata()
    {
        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->assertEquals($this->entityMetadata, $this->fieldMetadata->getEntityMetadata());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Entity metadata is not configured.
     */
    public function testGetEntityMetadataFails()
    {
        $this->fieldMetadata->getEntityMetadata();
    }

    public function testGetDoctrineMetadata()
    {
        $this->assertEquals($this->doctrineMetadata, $this->fieldMetadata->getDoctrineMetadata());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Doctrine metadata is not configured.
     */
    public function testGetDoctrineMetadataFails()
    {
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
            ->will($this->returnValue($className));
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
            ->will($this->returnValue($className));

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
            ->will($this->returnValue($className));

        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->fieldMetadata->set('source_class_name', $sourceClassName);

        $this->assertFalse($this->fieldMetadata->isDefinedBySourceEntity());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Cannot get field name from merge field metadata.
     */
    public function testGetFieldNameFails()
    {
        $this->fieldMetadata->getFieldName();
    }

    public function testGetMergeMode()
    {
        $mergeModes = array(MergeModes::REPLACE, MergeModes::UNITE);
        $this->assertNull($this->fieldMetadata->getMergeMode());

        $this->fieldMetadata->set('merge_modes', $mergeModes);
        $this->assertEquals(MergeModes::REPLACE, $this->fieldMetadata->getMergeMode());
    }

    public function testHasMergeMode()
    {
        $mergeModes = array(MergeModes::REPLACE);
        $this->fieldMetadata->set('merge_modes', $mergeModes);

        $this->assertTrue($this->fieldMetadata->hasMergeMode(MergeModes::REPLACE));
        $this->assertFalse($this->fieldMetadata->hasMergeMode(MergeModes::UNITE));
    }

    public function testAddMergeMode()
    {
        $this->assertEquals(array(), $this->fieldMetadata->getMergeModes());
        $this->fieldMetadata->addMergeMode(MergeModes::REPLACE);
        $this->fieldMetadata->addMergeMode(MergeModes::REPLACE);
        $this->assertEquals(array(MergeModes::REPLACE), $this->fieldMetadata->getMergeModes());
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
            ->will($this->returnValue(false));
        $this->assertFalse($this->fieldMetadata->isCollection());
    }

    public function testIsCollectionTrueWhenManyToMany()
    {
        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->will($this->returnValue(true));

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToMany')
            ->will($this->returnValue(true));

        $this->assertTrue($this->fieldMetadata->isCollection());
    }

    public function testIsCollectionTrueWhenOneToManyDefinedBySourceEntity()
    {
        $className = 'Foo\\Entity';

        $this->entityMetadata->expects($this->exactly(2))
            ->method('getClassName')
            ->will($this->returnValue($className));

        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);

        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->will($this->returnValue(true));

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToMany')
            ->will($this->returnValue(false));

        $this->doctrineMetadata->expects($this->once())
            ->method('isOneToMany')
            ->will($this->returnValue(true));

        $this->assertTrue($this->fieldMetadata->isCollection());
    }

    public function testIsCollectionTrueWhenManyToOneDefinedBySourceEntity()
    {
        $className = 'Foo\\Entity';
        $sourceClassName = 'Bar\\Entity';

        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue($className));

        $this->fieldMetadata->setEntityMetadata($this->entityMetadata);
        $this->fieldMetadata->set('source_class_name', $sourceClassName);

        $this->doctrineMetadata->expects($this->once())
            ->method('isAssociation')
            ->will($this->returnValue(true));

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToMany')
            ->will($this->returnValue(false));

        $this->doctrineMetadata->expects($this->once())
            ->method('isManyToOne')
            ->will($this->returnValue(true));

        $this->assertTrue($this->fieldMetadata->isCollection());
    }

    public function testOneToManyRelationShouldBeCloned()
    {
        $fieldMetadata = $this->getFieldMetadata([], [
            'type' => ClassMetadataInfo::ONE_TO_MANY,
            'orphanRemoval' => true,
            'targetEntity' => 'Foo\\Entity',
        ]);

        $this->assertTrue($fieldMetadata->shouldBeCloned());
    }

    /**
     * @param array $options
     * @param array $doctrineOptions
     *
     * @return FieldMetadata
     */
    protected function getFieldMetadata(array $options, array $doctrineOptions)
    {
        $doctrineMetadata = new DoctrineMetadata($doctrineOptions);

        return new FieldMetadata($options, $doctrineMetadata);
    }
}
