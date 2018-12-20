<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;

class AttributeManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testSetAttributes()
    {
        $attributeOne = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->getMock();
        $attributeOne->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('attr1'));

        $attributeTwo = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->getMock();
        $attributeTwo->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('attr2'));

        $attributeManager = new AttributeManager();

        $attributeManager->setAttributes(array($attributeOne, $attributeTwo));
        $attributes = $attributeManager->getAttributes();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attributes);
        $expected = array('attr1' => $attributeOne, 'attr2' => $attributeTwo);
        $this->assertEquals($expected, $attributes->toArray());

        $attributeCollection = new ArrayCollection(array('attr1' => $attributeOne, 'attr2' => $attributeTwo));
        $attributeManager->setAttributes($attributeCollection);
        $attributes = $attributeManager->getAttributes();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attributes);
        $expected = array('attr1' => $attributeOne, 'attr2' => $attributeTwo);
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
        $attribute = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->disableOriginalConstructor()
            ->getMock();
        $attribute->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test'));
        $attributes = new ArrayCollection(array('test' => $attribute));

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

        $attribute = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->disableOriginalConstructor()
            ->getMock();
        $attribute->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($entityAttributeName));
        $attributeManager->setAttributes(new ArrayCollection(array($attribute)));
        $this->assertSame($attribute, $attributeManager->getEntityAttribute());
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\UnknownAttributeException
     * @expectedExceptionMessage There is no entity attribute
     */
    public function testEntityAttributeException()
    {
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

    /**
     * @param string $name
     * @param string $type
     * @return Attribute
     */
    private function getAttributeObject($name, $type)
    {
        $attribute = new Attribute();
        $attribute
            ->setName($name)
            ->setType($type);

        return $attribute;
    }
}
