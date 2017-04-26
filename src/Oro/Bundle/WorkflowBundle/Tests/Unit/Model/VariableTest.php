<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class VariableTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     * @param bool $testDefaultValue
     */
    public function testGettersAndSetters($property, $value, $testDefaultValue)
    {
        $setter = 'set' . ucfirst($property);
        $obj = new Variable();
        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Variable',
            call_user_func_array([$obj, $setter], [$value])
        );

        static::assertPropertyAccessors($obj, [
            [$property, $value, $testDefaultValue]
        ]);
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            'name' => ['name', 'test', false],
            'label' => ['label', 'test', false],
            'value' => ['value', 'my_string', false],
            'type' => ['type', 'string', false],
            'options' => ['options', ['key' => 'value'], false],
            'options_default' => ['options', [], true]
        ];
    }

    /**
     * Test get/set options
     */
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

    /**
     * Test instance and internal type
     */
    public function testInstanceAndInternalType()
    {
        $variable = new Variable();
        $this->assertInstanceOf('Oro\Bundle\ActionBundle\Model\ParameterInterface', $variable);

        $this->assertEquals('variable', $variable->getInternalType());
    }
}
