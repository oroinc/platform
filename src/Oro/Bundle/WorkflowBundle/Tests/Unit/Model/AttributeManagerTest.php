<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Model\AttributeManager;

class AttributeManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSetAttributes()
    {
        $attributeOne = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Attribute')
            ->getMock();
        $attributeOne->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('attr1'));

        $attributeTwo = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Attribute')
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
        $attribute = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Attribute')
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
}
