<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataBuilder;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

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

    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityExtendConfigProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $additionalMetadataProvider;

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

        $this->entityExtendConfigProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataBuilder = new MetadataBuilder(
            $this->metadataFactory,
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->entityExtendConfigProvider
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

        $this->entityExtendConfigProvider->expects($this->any())
                ->method('getConfigs')
                ->with(static::CLASS_NAME)
                ->will($this->returnValue([]));

        $this->classMetadata->name = static::CLASS_NAME;
        $this->classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(['id']));

        // Test creation of entity metadata
        $entityMetadataCallIndex = 0;
        $entityMetadata = $this->createEntityMetadata();

        $metadataFactoryCallIndex = 0;
        $this->metadataFactory->expects($this->at($metadataFactoryCallIndex++))
            ->method('createEntityMetadata')
            ->with([], $this->isType('array'))
            ->will($this->returnValue($entityMetadata));

        // Test adding doctrine fields
        $doctrineFieldNames = ['id', 'foo_field', 'bar_field'];
        $doctrineFieldNamesWithoutId = ['foo_field', 'bar_field'];

        $idFieldNames = ['id'];
        $this->classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue($idFieldNames));

        $this->classMetadata->expects($this->any())
            ->method('getFieldNames')
            ->will($this->returnValue($doctrineFieldNames));

        $this->classMetadata->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnCallback(function ($fieldName) {
                return ['fieldName' => $fieldName];
            }));

        foreach ($doctrineFieldNamesWithoutId as $fieldName) {
            $fieldMapping = ['fieldName' => $fieldName];

            $fieldMetadata = $this->createFieldMetadata();

            $this->metadataFactory->expects($this->at($metadataFactoryCallIndex++))
                ->method('createFieldMetadata')
                ->with(['field_name' => $fieldName], $fieldMapping)
                ->will($this->returnValue($fieldMetadata));

            $entityMetadata->expects($this->at($entityMetadataCallIndex++))
                ->method('addFieldMetadata')
                ->with($fieldMetadata);
        }

        // Test adding doctrine associations
        $associationMappings = [
            'foo_association' => ['foo' => 'bar'],
            'bar_association' => ['bar' => 'baz']
        ];

        $this->classMetadata->expects($this->any())
            ->method('getAssociationNames')
            ->will($this->returnValue(array_keys($associationMappings)));

        $this->classMetadata->expects($this->any())
            ->method('getAssociationMapping')
            ->will($this->returnCallback(function ($association) use ($associationMappings) {
                return $associationMappings[$association];
            }));

        foreach ($associationMappings as $fieldName => $associationMapping) {
            $fieldMetadata = $this->createFieldMetadata();

            $this->metadataFactory->expects($this->at($metadataFactoryCallIndex++))
                ->method('createFieldMetadata')
                ->with(['field_name' => $fieldName], $associationMapping)
                ->will($this->returnValue($fieldMetadata));

            $entityMetadata->expects($this->at($entityMetadataCallIndex++))
                ->method('addFieldMetadata')
                ->with($fieldMetadata);
        }

        $inversedUnidirectionalAssociationMappings = [
            [
                'fieldName' => 'foo_association',
                'type' => ClassMetadataInfo::ONE_TO_MANY,
                'sourceEntity' => 'Namespace\\FooEntity',
                'targetEntity' => 'Namespace\\EntityName',
                'mappedBySourceEntity' => false,
                '_generatedFieldName' => 'Namespace_FooEntity_foo_association',
            ],
            [
                'fieldName' => 'bar_association',
                'type' => ClassMetadataInfo::ONE_TO_MANY,
                'sourceEntity' => 'Namespace\\BarEntity',
                'targetEntity' => 'Namespace\\EntityName',
                'mappedBySourceEntity' => false,
                '_generatedFieldName' => 'Namespace_BarEntity_bar_association',
            ],
            [
                'fieldName' => 'bar_association',
                'type' => ClassMetadataInfo::ONE_TO_ONE,
                'sourceEntity' => 'Namespace\\FooBarEntity',
                'targetEntity' => 'Namespace\\EntityName',
                'mappedBySourceEntity' => false,
                '_generatedFieldName' => 'Namespace_FooBarEntity_bar_association',
            ],
        ];
        $this->doctrineHelper->expects($this->once())
            ->method('getInversedUnidirectionalAssociationMappings')
            ->willReturn($inversedUnidirectionalAssociationMappings);

        foreach ($inversedUnidirectionalAssociationMappings as $associationMapping) {
            $fieldMetadata = $this->createFieldMetadata();

            $this->metadataFactory->expects($this->at($metadataFactoryCallIndex++))
                ->method('createFieldMetadata')
                ->will($this->returnValue($fieldMetadata));

            $entityMetadata->expects($this->at($entityMetadataCallIndex++))
                ->method('addFieldMetadata')
                ->with($fieldMetadata);
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
