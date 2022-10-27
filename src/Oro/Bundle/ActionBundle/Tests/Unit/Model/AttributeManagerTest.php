<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Exception\UnknownAttributeException;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;

class AttributeManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testSetAttributes()
    {
        $attributeOne = $this->createMock(Attribute::class);
        $attributeOne->expects($this->any())
            ->method('getName')
            ->willReturn('attr1');

        $attributeTwo = $this->createMock(Attribute::class);
        $attributeTwo->expects($this->any())
            ->method('getName')
            ->willReturn('attr2');

        $attributeManager = new AttributeManager();

        $attributeManager->setAttributes([$attributeOne, $attributeTwo]);
        $attributes = $attributeManager->getAttributes();
        $this->assertInstanceOf(ArrayCollection::class, $attributes);
        $expected = ['attr1' => $attributeOne, 'attr2' => $attributeTwo];
        $this->assertEquals($expected, $attributes->toArray());

        $attributeCollection = new ArrayCollection(['attr1' => $attributeOne, 'attr2' => $attributeTwo]);
        $attributeManager->setAttributes($attributeCollection);
        $attributes = $attributeManager->getAttributes();
        $this->assertInstanceOf(ArrayCollection::class, $attributes);
        $expected = ['attr1' => $attributeOne, 'attr2' => $attributeTwo];
        $this->assertEquals($expected, $attributes->toArray());
    }

    public function testGetStepAttributes()
    {
        $attributes = new ArrayCollection();
        $attributeManager = new AttributeManager();
        $attributeManager->setAttributes($attributes);
        $this->assertEquals($attributes, $attributeManager->getAttributes());
    }

    public function testGetAttribute()
    {
        $attribute = $this->createMock(Attribute::class);
        $attribute->expects($this->any())
            ->method('getName')
            ->willReturn('test');
        $attributes = new ArrayCollection(['test' => $attribute]);

        $attributeManager = new AttributeManager();
        $attributeManager->setAttributes($attributes);
        $this->assertSame($attribute, $attributeManager->getAttribute('test'));
    }

    public function testEntityAttribute()
    {
        $attributeManager = new AttributeManager();
        $entityAttributeName = 'test';
        $this->assertSame($attributeManager, $attributeManager->setEntityAttributeName($entityAttributeName));
        $this->assertEquals($entityAttributeName, $attributeManager->getEntityAttributeName());

        $attribute = $this->createMock(Attribute::class);
        $attribute->expects($this->any())
            ->method('getName')
            ->willReturn($entityAttributeName);
        $attributeManager->setAttributes(new ArrayCollection([$attribute]));
        $this->assertSame($attribute, $attributeManager->getEntityAttribute());
    }

    public function testEntityAttributeException()
    {
        $this->expectException(UnknownAttributeException::class);
        $this->expectExceptionMessage('There is no entity attribute');

        $attributeManager = new AttributeManager();
        $entityAttributeName = 'test';
        $attributeManager->setEntityAttributeName($entityAttributeName);
        $attributeManager->getEntityAttribute();
    }

    public function testGetAttributesByType()
    {
        $attribute1 = $this->getAttributeObject('test1', 'string');
        $attribute2 = $this->getAttributeObject('test2', 'integer');
        $attribute3 = $this->getAttributeObject('test3', 'string');

        $attributeManager = new AttributeManager();
        $attributeManager->setAttributes(new ArrayCollection([
            'test1' => $attribute1,
            'test2' => $attribute2,
            'test3' => $attribute3,
        ]));

        $stringTypeAttributes = $attributeManager->getAttributesByType('string');
        $expectedAttributes = new ArrayCollection([
            'test1' => $attribute1,
            'test3' => $attribute3,
        ]);

        $this->assertCount(2, $stringTypeAttributes);
        $this->assertEquals($expectedAttributes, $stringTypeAttributes);
    }

    private function getAttributeObject(string $name, string $type): Attribute
    {
        $attribute = new Attribute();
        $attribute
            ->setName($name)
            ->setType($type);

        return $attribute;
    }
}
