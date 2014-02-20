<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataBuilder;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class MetadataBuilderTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Namespace\EntityName';

    /**
     * @var MetadataBuilder
     */
    protected $metadataBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $classMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');

        $this->metadataFactory = $this
            ->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->classMetadata = $this->createClassMetadata();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Doctrine\\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataBuilder = new MetadataBuilder(
            $this->metadataFactory,
            $this->doctrineHelper,
            $this->eventDispatcher
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEntityMetadataByClass()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::CLASS_NAME)
            ->will($this->returnValue($this->classMetadata));

        // Test creation of entity metadata
        $entityMetadataCallIndex = 0;
        $entityMetadata = $this->createEntityMetadata();

        $metadataFactoryCallIndex = 0;
        $this->metadataFactory->expects($this->at($metadataFactoryCallIndex++))
            ->method('createEntityMetadata')
            ->with(array(), $this->isType('array'))
            ->will($this->returnValue($entityMetadata));

        // Test adding doctrine fields
        $doctrineFieldNames = array('foo_field', 'bar_field');
        $classMetadataCallIndex = 0;

        $this->classMetadata->expects($this->at($classMetadataCallIndex++))
            ->method('getFieldNames')
            ->will($this->returnValue($doctrineFieldNames));

        foreach ($doctrineFieldNames as $fieldName) {
            $fieldMapping = array('fieldName' => $fieldName);

            $this->classMetadata->expects($this->at($classMetadataCallIndex++))
                ->method('getFieldMapping')
                ->with($fieldName)
                ->will($this->returnValue($fieldMapping));

            $fieldMetadata = $this->createFieldMetadata();

            $this->metadataFactory->expects($this->at($metadataFactoryCallIndex++))
                ->method('createFieldMetadata')
                ->with(array('field_name' => $fieldName), $fieldMapping)
                ->will($this->returnValue($fieldMetadata));

            $entityMetadata->expects($this->at($entityMetadataCallIndex++))
                ->method('addFieldMetadata')
                ->with($fieldMetadata);
        }

        // Test adding doctrine associations
        $associationMappings = array(
            'foo_association' => array('foo' => 'bar'),
            'bar_association' => array('bar' => 'baz')
        );

        $this->classMetadata->expects($this->at($classMetadataCallIndex++))
            ->method('getAssociationMappings')
            ->will($this->returnValue($associationMappings));

        foreach ($associationMappings as $fieldName => $associationMapping) {
            $fieldMetadata = $this->createFieldMetadata();

            $this->metadataFactory->expects($this->at($metadataFactoryCallIndex++))
                ->method('createFieldMetadata')
                ->with(array('field_name' => $fieldName), $associationMapping)
                ->will($this->returnValue($fieldMetadata));

            $entityMetadata->expects($this->at($entityMetadataCallIndex++))
                ->method('addFieldMetadata')
                ->with($fieldMetadata);
        }

        // Test adding doctrine inverse associations
        $allMetadata = array(
            self::CLASS_NAME => $this->classMetadata,
            'Namespace\\FooEntity' => $fooClassMetadata = $this->createClassMetadata(),
            'Namespace\\BarEntity' => $barClassMetadata = $this->createClassMetadata(),
        );

        $expectedClassesData = array(
            'Namespace\\FooEntity' => array(
                'associationMappings' => array(
                    'foo_association' => array('foo' => 'bar'),
                ),
                'expectedFields' => array(
                    'foo_association' => array(
                        'field_name' => 'Namespace_FooEntity_foo_association',
                        'merge_modes' => array(MergeModes::UNITE),
                        'source_field_name' => 'foo_association',
                        'source_class_name' => 'Namespace\\FooEntity',
                    ),
                )
            ),
            'Namespace\\BarEntity' => array(
                'associationMappings' => array(
                    'bar_association' => array('bar' => 'baz'),
                    'skipped_many_to_many' => array('type' => ClassMetadataInfo::MANY_TO_MANY),
                    'skipped_mapped_by' => array('mappedBy' => self::CLASS_NAME),
                ),
                'expectedFields' => array(
                    'bar_association' => array(
                        'field_name' => 'Namespace_BarEntity_bar_association',
                        'merge_modes' => array(MergeModes::UNITE),
                        'source_field_name' => 'bar_association',
                        'source_class_name' => 'Namespace\\BarEntity',
                    ),
                )
            )
        );

        $this->doctrineHelper->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue(array_values($allMetadata)));

        foreach ($expectedClassesData as $className => $expectedData) {
            $metadata = $allMetadata[$className];
            $metadata->expects($this->once())
                ->method('getName')
                ->will($this->returnValue($className));

            $metadata->expects($this->once())
                ->method('getAssociationsByTargetClass')
                ->with(self::CLASS_NAME)
                ->will($this->returnValue($expectedData['associationMappings']));

            foreach ($expectedData['expectedFields'] as $fieldName => $expectedOptions) {
                $expectedAssociationMapping = $expectedData['associationMappings'][$fieldName];
                $expectedAssociationMapping['mappedBySourceEntity'] = false;

                $fieldMetadata = $this->createFieldMetadata();

                $this->metadataFactory->expects($this->at($metadataFactoryCallIndex++))
                    ->method('createFieldMetadata')
                    ->with($expectedOptions, $expectedAssociationMapping)
                    ->will($this->returnValue($fieldMetadata));

                $entityMetadata->expects($this->at($entityMetadataCallIndex++))
                    ->method('addFieldMetadata')
                    ->with($fieldMetadata);
            }
        }

        // Test event dispatcher
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(MergeEvents::BUILD_METADATA, new EntityMetadataEvent($entityMetadata));

        $this->assertEquals($entityMetadata, $this->metadataBuilder->createEntityMetadataByClass(self::CLASS_NAME));
    }

    protected function createClassMetadata()
    {
        return $this->getMockBuilder('Doctrine\\ORM\\Mapping\\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createEntityMetadata()
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFieldMetadata()
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createDoctrineMetadata()
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\DoctrineMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
