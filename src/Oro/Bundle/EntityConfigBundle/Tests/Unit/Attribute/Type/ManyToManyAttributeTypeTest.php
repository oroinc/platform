<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\ManyToManyAttributeType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ManyToManyAttributeTypeTest extends AttributeTypeTestCase
{
    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    protected $metadata;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata = $this->createMock(ClassMetadata::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->with(self::CLASS_NAME)
            ->willReturn($this->metadata);

        return new ManyToManyAttributeType($this->entityNameResolver, $doctrineHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => true, 'isFilterable' => true, 'isSortable' => false]
        ];
    }

    public function testIsSortable()
    {
        $this->attribute->fromArray('extend', ['target_entity' => LocalizedFallbackValue::class]);

        $this->assertTrue($this->getAttributeType()->isSortable($this->attribute));
    }

    public function testGetSearchableValue()
    {
        $value1 = new LocalizedFallbackValue();
        $value1->setString('test')->setLocalization($this->localization);

        $value2 = new \stdClass();

        $this->assertSame(
            'resolved Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue name in de locale ' .
            'resolved stdClass name in de locale',
            $this->getAttributeType()
                ->getSearchableValue($this->attribute, new ArrayCollection([$value1, $value2]), $this->localization)
        );
    }

    public function testGetSearchableException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be an array or Traversable, [string] given');

        $this->getAttributeType()->getSearchableValue($this->attribute, '', $this->localization);
    }

    public function testGetSearchableValueLocalizable()
    {
        $this->attribute->fromArray('extend', ['target_entity' => LocalizedFallbackValue::class]);

        $value = new LocalizedFallbackValue();
        $value->setString('test')->setLocalization($this->localization);

        $this->assertSame(
            'test',
            $this->getAttributeType()
                ->getSearchableValue($this->attribute, new ArrayCollection([$value]), $this->localization)
        );
    }

    public function testGetFilterableValue()
    {
        $value1 = new LocalizedFallbackValue();
        $value1->setString('test')->setLocalization($this->localization);

        $value2 = new \stdClass();

        $this->assertSame(
            'resolved Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue name in de locale ' .
            'resolved stdClass name in de locale',
            $this->getAttributeType()
                ->getFilterableValue($this->attribute, new ArrayCollection([$value1, $value2]), $this->localization)
        );
    }

    public function testGetFilterableValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be an array or Traversable, [string] given');

        $this->getAttributeType()->getFilterableValue($this->attribute, '', $this->localization);
    }

    public function testGetFilterableValueLocalizableByExtendedScope()
    {
        $this->attribute->fromArray('extend', ['target_entity' => LocalizedFallbackValue::class]);

        $this->metadata->expects($this->never())
            ->method($this->anything());

        $value = new LocalizedFallbackValue();
        $value->setString('test')->setLocalization($this->localization);

        $this->assertSame(
            'test',
            $this->getAttributeType()
                ->getFilterableValue($this->attribute, new ArrayCollection([$value]), $this->localization)
        );
    }

    public function testGetFilterableValueNoDoctrineMetadata()
    {
        $this->attribute->fromArray('extend', []);

        $this->metadata->expects($this->once())
            ->method('hasAssociation')
            ->with(self::FIELD_NAME)
            ->willReturn(false);
        $this->metadata->expects($this->never())
            ->method('getAssociationMapping');

        $value = new LocalizedFallbackValue();
        $value->setString('test')->setLocalization($this->localization);

        $this->assertSame(
            'resolved Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue name in de locale',
            $this->getAttributeType()
                ->getFilterableValue($this->attribute, new ArrayCollection([$value]), $this->localization)
        );
    }

    public function testGetFilterableValueLocalizableByDoctrineMetadata()
    {
        $this->attribute->fromArray('extend', []);

        $this->metadata->expects($this->once())
            ->method('hasAssociation')
            ->with(self::FIELD_NAME)
            ->willReturn(true);
        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with(self::FIELD_NAME)
            ->willReturn(['targetEntity' => LocalizedFallbackValue::class]);

        $value = new LocalizedFallbackValue();
        $value->setString('test')->setLocalization($this->localization);

        $this->assertSame(
            'test',
            $this->getAttributeType()
                ->getFilterableValue($this->attribute, new ArrayCollection([$value]), $this->localization)
        );
    }

    public function testGetSortableValueByExtendedScope()
    {
        $this->attribute->fromArray('extend', ['target_entity' => LocalizedFallbackValue::class]);

        $this->metadata->expects($this->never())
            ->method($this->anything());

        $value = new LocalizedFallbackValue();
        $value->setString('test')->setLocalization($this->localization);

        $this->assertSame(
            'test',
            $this->getAttributeType()
                ->getSortableValue($this->attribute, new ArrayCollection([$value]), $this->localization)
        );
    }

    public function testGetSortableValueByDoctrineMetadata()
    {
        $this->attribute->fromArray('extend', []);

        $this->metadata->expects($this->once())
            ->method('hasAssociation')
            ->with(self::FIELD_NAME)
            ->willReturn(true);
        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with(self::FIELD_NAME)
            ->willReturn(['targetEntity' => LocalizedFallbackValue::class]);

        $value = new LocalizedFallbackValue();
        $value->setString('test')->setLocalization($this->localization);

        $this->assertSame(
            'test',
            $this->getAttributeType()
                ->getSortableValue($this->attribute, new ArrayCollection([$value]), $this->localization)
        );
    }

    public function testGetSortableValueNoDoctrineMetadata()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->attribute->fromArray('extend', []);

        $this->metadata->expects($this->once())
            ->method('hasAssociation')
            ->with(self::FIELD_NAME)
            ->willReturn(false);
        $this->metadata->expects($this->never())
            ->method('getAssociationMapping');

        $value = new LocalizedFallbackValue();
        $value->setString('test')->setLocalization($this->localization);

        $this->getAttributeType()
            ->getSortableValue($this->attribute, new ArrayCollection([$value]), $this->localization);
    }

    public function testGetSortableValueException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization);
    }

    public function testGetSortableValueExceptionByExtendedScope()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->attribute->fromArray('extend', ['target_entity' => \stdClass::class]);

        $this->metadata->expects($this->never())
            ->method($this->anything());

        $this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization);
    }

    public function testGetSortableValueExceptionByDoctrineMetadata()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->attribute->fromArray('extend', []);

        $this->metadata->expects($this->once())
            ->method('hasAssociation')
            ->with(self::FIELD_NAME)
            ->willReturn(true);
        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with(self::FIELD_NAME)
            ->willReturn(['targetEntity' => \stdClass::class]);

        $this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization);
    }
}
