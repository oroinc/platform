<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Variable;

class VariableTest extends \PHPUnit_Framework_TestCase
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
        $obj = new Variable();
        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Variable',
            call_user_func_array([$obj, $setter], [$value])
        );
        $this->assertEquals($value, call_user_func_array([$obj, $getter], []));
    }

    public function propertiesDataProvider()
    {
        return [
            'name'    => ['name', 'test'],
            'label'   => ['label', 'test'],
            'value'   => ['value', 'my_string'],
            'type'    => ['type', 'string'],
            'options' => ['options', ['key' => 'value']]
        ];
    }

    public function testGetSetOption()
    {
        $obj = new Variable();
        $obj->setOptions(['key' => 'test']);
        $this->assertEquals('test', $obj->getOption('key'));
        $obj->setOption('key2', 'test2');
        $this->assertEquals(['key' => 'test', 'key2' => 'test2'], $obj->getOptions());
        $obj->setOption('key', 'test_changed');
        $this->assertEquals('test_changed', $obj->getOption('key'));
    }

    public function testInstanceAndInternalType()
    {
        $variable = new Variable();
        $this->assertInstanceOf('Oro\Bundle\ActionBundle\Model\ParameterInterface', $variable);

        $this->assertEquals('variable', $variable->getInternalType());
        $this->assertTrue($variable->isInternalType('variable'));
    }
}
