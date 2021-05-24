<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataBuilder;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MetadataBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'Namespace\EntityName';

    /** @var MetadataFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataFactory;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityExtendConfigProvider;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $classMetadata;

    /** @var MetadataBuilder */
    private $metadataBuilder;

    protected function setUp(): void
    {
        $this->metadataFactory = $this->createMock(MetadataFactory::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityExtendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);

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
            ->willReturn($this->classMetadata);

        $this->entityExtendConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->with(self::CLASS_NAME)
            ->willReturn([]);

        $this->classMetadata->name = self::CLASS_NAME;
        $this->classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        // Test creation of entity metadata
        $entityMetadata = $this->createMock(EntityMetadata::class);
        $this->metadataFactory->expects($this->once())
            ->method('createEntityMetadata')
            ->with([], $this->isType('array'))
            ->willReturn($entityMetadata);

        // Test adding doctrine fields
        $doctrineFieldNames = ['id', 'foo_field', 'bar_field'];
        $doctrineFieldNamesWithoutId = ['foo_field', 'bar_field'];

        $idFieldNames = ['id'];
        $this->classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn($idFieldNames);
        $this->classMetadata->expects($this->any())
            ->method('getFieldNames')
            ->willReturn($doctrineFieldNames);
        $this->classMetadata->expects($this->any())
            ->method('getFieldMapping')
            ->willReturnCallback(function ($fieldName) {
                return ['fieldName' => $fieldName];
            });

        // Test adding doctrine associations
        $associationMappings = [
            'foo_association' => ['foo' => 'bar'],
            'bar_association' => ['bar' => 'baz']
        ];

        $this->classMetadata->expects($this->any())
            ->method('getAssociationNames')
            ->willReturn(array_keys($associationMappings));

        $this->classMetadata->expects($this->any())
            ->method('getAssociationMapping')
            ->willReturnCallback(function ($association) use ($associationMappings) {
                return $associationMappings[$association];
            });

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

        $createFieldMetadataExpectations = [];
        $createFieldMetadataResults = [];
        $addFieldMetadataExpectations = [];
        foreach ($doctrineFieldNamesWithoutId as $fieldName) {
            $fieldMetadata = $this->createMock(FieldMetadata::class);
            $createFieldMetadataExpectations[] = [['field_name' => $fieldName], ['fieldName' => $fieldName]];
            $createFieldMetadataResults[] = $fieldMetadata;
            $addFieldMetadataExpectations[] = [$this->identicalTo($fieldMetadata)];
        }
        foreach ($associationMappings as $fieldName => $associationMapping) {
            $fieldMetadata = $this->createMock(FieldMetadata::class);
            $createFieldMetadataExpectations[] = [['field_name' => $fieldName], $associationMapping];
            $createFieldMetadataResults[] = $fieldMetadata;
            $addFieldMetadataExpectations[] = [$this->identicalTo($fieldMetadata)];
        }
        foreach ($inversedUnidirectionalAssociationMappings as $associationMapping) {
            $fieldMetadata = $this->createMock(FieldMetadata::class);
            $createFieldMetadataExpectations[] = [$this->isType('array'), $associationMapping];
            $createFieldMetadataResults[] = $fieldMetadata;
            $addFieldMetadataExpectations[] = [$this->identicalTo($fieldMetadata)];
        }
        $this->metadataFactory->expects($this->exactly(count($createFieldMetadataExpectations)))
            ->method('createFieldMetadata')
            ->withConsecutive(...$createFieldMetadataExpectations)
            ->willReturnOnConsecutiveCalls(...$createFieldMetadataResults);
        $entityMetadata->expects($this->exactly(count($createFieldMetadataExpectations)))
            ->method('addFieldMetadata')
            ->withConsecutive(...$addFieldMetadataExpectations);

        // Test event dispatcher
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new EntityMetadataEvent($entityMetadata), MergeEvents::BUILD_METADATA);

        $this->assertEquals(
            $entityMetadata,
            $this->metadataBuilder->createEntityMetadataByClass(self::CLASS_NAME)
        );
    }
}
