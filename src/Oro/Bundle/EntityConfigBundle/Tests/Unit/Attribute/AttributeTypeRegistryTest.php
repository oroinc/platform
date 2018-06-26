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

class AttributeTypeRegistryTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = \stdClass::class;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    protected $metadata;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var AttributeTypeRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->registry = new AttributeTypeRegistry($this->doctrineHelper);
        $this->registry->addAttributeType($this->getAttributeType('test_type'));
        $this->registry->addAttributeType($this->getAttributeType('metadata_type'));
        $this->registry->addAttributeType($this->getAttributeType(RelationType::ONE_TO_ONE));
        $this->registry->addAttributeType($this->getAttributeType(RelationType::ONE_TO_MANY));
        $this->registry->addAttributeType($this->getAttributeType(RelationType::MANY_TO_ONE));
        $this->registry->addAttributeType($this->getAttributeType(RelationType::MANY_TO_MANY));
    }

    public function testGetAttributeTypeKnownType()
    {
        $this->doctrineHelper->expects($this->never())->method($this->anything());

        $this->assertEquals(
            $this->getAttributeType('test_type'),
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

        $this->assertEquals(
            $this->getAttributeType($metadataType),
            $this->registry->getAttributeType($this->getAttribute($fieldName, 'some_type'))
        );
    }

    /**
     * @dataProvider relationMetadataProvider
     *
     * @param integer $type
     * @param AttributeTypeInterface|null $expected
     */
    public function testGetAttributeTypeRelationFromMetadata($type, $expected)
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

        $this->assertEquals($expected, $this->registry->getAttributeType($this->getAttribute($fieldName, 'some_type')));
    }

    /**
     * @return \Generator
     */
    public function relationMetadataProvider()
    {
        yield 'ONE_TO_ONE' => [
            'type' => ClassMetadataInfo::ONE_TO_ONE,
            'expected' => $this->getAttributeType(RelationType::ONE_TO_ONE)
        ];

        yield 'ONE_TO_MANY' => [
            'type' => ClassMetadataInfo::ONE_TO_MANY,
            'expected' => $this->getAttributeType(RelationType::ONE_TO_MANY)
        ];

        yield 'MANY_TO_ONE' => [
            'type' => ClassMetadataInfo::MANY_TO_ONE,
            'expected' => $this->getAttributeType(RelationType::MANY_TO_ONE)
        ];

        yield 'MANY_TO_MANY' => [
            'type' => ClassMetadataInfo::MANY_TO_MANY,
            'expected' => $this->getAttributeType(RelationType::MANY_TO_MANY)
        ];

        yield 'unknown' => [
            'type' => 100,
            'expected' => null
        ];
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

    /**
     * @param string $name
     *
     * @return AttributeTypeInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getAttributeType($name)
    {
        $type = $this->createMock(AttributeTypeInterface::class);
        $type->expects($this->any())->method('getType')->willReturn($name);

        return $type;
    }

    /**
     * @param string $fieldName
     * @param string $type
     *
     * @return FieldConfigModel
     */
    protected function getAttribute($fieldName, $type)
    {
        $attribute = new FieldConfigModel($fieldName, $type);
        $attribute->setEntity(new EntityConfigModel(self::CLASS_NAME));

        return $attribute;
    }
}
