<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Attribute;

class AttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGettersAndSetters($property, $value)
    {
        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);
        $obj = new Attribute();
        $this->assertInstanceOf(
            'Oro\Bundle\ActionBundle\Model\Attribute',
            call_user_func_array(array($obj, $setter), array($value))
        );
        $this->assertEquals($value, call_user_func_array(array($obj, $getter), array()));
    }

    public function propertiesDataProvider()
    {
        return array(
            'name' => array('name', 'test'),
            'label' => array('label', 'test'),
            'type' => array('type', 'string'),
            'options' => array('options', array('key' => 'value'))
        );
    }

    public function testGetSetOption()
    {
        $obj = new Attribute();
        $obj->setOptions(array('key' => 'test'));
        $this->assertEquals('test', $obj->getOption('key'));
        $obj->setOption('key2', 'test2');
        $this->assertEquals(array('key' => 'test', 'key2' => 'test2'), $obj->getOptions());
        $obj->setOption('key', 'test_changed');
        $this->assertEquals('test_changed', $obj->getOption('key'));
    }

    public function testEntityAclAllowed()
    {
        $attribute = new Attribute();

        $this->assertTrue($attribute->isEntityUpdateAllowed());
        $this->assertTrue($attribute->isEntityDeleteAllowed());

        $attribute->setEntityAcl(array('update' => false, 'delete' => false));
        $this->assertFalse($attribute->isEntityUpdateAllowed());
        $this->assertFalse($attribute->isEntityDeleteAllowed());

        $attribute->setEntityAcl(array('update' => true, 'delete' => true));
        $this->assertTrue($attribute->isEntityUpdateAllowed());
        $this->assertTrue($attribute->isEntityDeleteAllowed());
    }

    public function testInstanceAndInternalType()
    {
        $attribute = new Attribute();
        $this->assertInstanceOf('Oro\Bundle\ActionBundle\Model\EntityParameterInterface', $attribute);
        $this->assertInstanceOf('Oro\Bundle\ActionBundle\Model\ParameterInterface', $attribute);

        $this->assertEquals('attribute', $attribute->getInternalType());
    }
}
