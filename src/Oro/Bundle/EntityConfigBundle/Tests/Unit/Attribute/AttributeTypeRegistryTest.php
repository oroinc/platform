<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class AttributeTypeRegistryTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = \stdClass::class;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AttributeTypeRegistry */
    private $registry;

    /** @var array */
    private $attributeTypes;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->attributeTypes = [
            'test_type'                => $this->createMock(AttributeTypeInterface::class),
            'metadata_type'            => $this->createMock(AttributeTypeInterface::class),
            RelationType::ONE_TO_ONE   => $this->createMock(AttributeTypeInterface::class),
            RelationType::ONE_TO_MANY  => $this->createMock(AttributeTypeInterface::class),
            RelationType::MANY_TO_ONE  => $this->createMock(AttributeTypeInterface::class),
            RelationType::MANY_TO_MANY => $this->createMock(AttributeTypeInterface::class)
        ];

        $containerBuilder = TestContainerBuilder::create();
        foreach ($this->attributeTypes as $type => $service) {
            $containerBuilder->add($type, $service);
        }

        $this->registry = new AttributeTypeRegistry(
            $containerBuilder->getContainer($this),
            $this->doctrineHelper
        );
    }

    public function testGetAttributeTypeKnownType()
    {
        $this->doctrineHelper->expects($this->never())->method($this->anything());

        $this->assertSame(
            $this->attributeTypes['test_type'],
            $this->registry->getAttributeType($this->getAttribute(null, 'test_type'))
        );
    }

    public function testGetAttributeTypeFieldFromMetadata()
    {
        $metadataType = 'metadata_type';
        $fieldName = 'test_field';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with(self::CLASS_NAME)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('hasField')
            ->with($fieldName)
            ->willReturn(true);
        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with($fieldName)
            ->willReturn($metadataType);

        $this->assertSame(
            $this->attributeTypes[$metadataType],
            $this->registry->getAttributeType($this->getAttribute($fieldName, 'some_type'))
        );
    }

    public function testGetAttributeTypeRelationFromMetadataForOneToOne()
    {
        $this->doTestGetAttributeTypeRelationFromMetadata(
            ClassMetadataInfo::ONE_TO_ONE,
            $this->attributeTypes[RelationType::ONE_TO_ONE]
        );
    }

    public function testGetAttributeTypeRelationFromMetadataForOneToMany()
    {
        $this->doTestGetAttributeTypeRelationFromMetadata(
            ClassMetadataInfo::ONE_TO_MANY,
            $this->attributeTypes[RelationType::ONE_TO_MANY]
        );
    }

    public function testGetAttributeTypeRelationFromMetadataForManyToOne()
    {
        $this->doTestGetAttributeTypeRelationFromMetadata(
            ClassMetadataInfo::MANY_TO_ONE,
            $this->attributeTypes[RelationType::MANY_TO_ONE]
        );
    }

    public function testGetAttributeTypeRelationFromMetadataForManyToMany()
    {
        $this->doTestGetAttributeTypeRelationFromMetadata(
            ClassMetadataInfo::MANY_TO_MANY,
            $this->attributeTypes[RelationType::MANY_TO_MANY]
        );
    }

    public function testGetAttributeTypeRelationFromMetadataForUnknown()
    {
        $this->doTestGetAttributeTypeRelationFromMetadata(
            100,
            null
        );
    }

    public function testGetAttributeTypeUnknown()
    {
        $fieldName = 'test_field';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with(self::CLASS_NAME)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('hasField')
            ->with($fieldName)
            ->willReturn(false);
        $this->metadata->expects($this->once())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(false);
        $this->metadata->expects($this->never())->method('getAssociationMapping');

        $this->assertNull($this->registry->getAttributeType($this->getAttribute($fieldName, 'some_type')));
    }

    private function getAttribute(?string $fieldName, string $type): FieldConfigModel
    {
        $attribute = new FieldConfigModel($fieldName, $type);
        $attribute->setEntity(new EntityConfigModel(self::CLASS_NAME));

        return $attribute;
    }

    private function doTestGetAttributeTypeRelationFromMetadata(int $type, ?AttributeTypeInterface $expected)
    {
        $fieldName = 'test_field';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with(self::CLASS_NAME)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('hasField')
            ->with($fieldName)
            ->willReturn(false);
        $this->metadata->expects($this->once())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(true);
        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->willReturn(['type' => $type]);

        $this->assertSame(
            $expected,
            $this->registry->getAttributeType($this->getAttribute($fieldName, 'some_type'))
        );
    }
}
