<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class VariableTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     * @param bool $testDefaultValue
     */
    public function testGettersAndSetters($property, $value, $testDefaultValue = false)
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
            'name' => ['name', 'test'],
            'label' => ['label', 'test'],
            'value' => ['value', 'my_string'],
            'type' => ['type', 'string'],
            'options' => ['options', ['key' => 'value']],
            'options_default' => ['options', [], true],
            'property_path' => ['propertyPath', 'entity.field']
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
     * Test get form options
     */
    public function testGetFormOptions()
    {
        $obj = new Variable();

        $obj->setOptions(['form_options' => ['key' => 'test']]);
        $this->assertEquals(['key' => 'test'], $obj->getFormOptions());
    }

    /**
     * Test entity ACL
     */
    public function testEntityAclAllowed()
    {
        $variable = new Variable();

        $this->assertTrue($variable->isEntityUpdateAllowed());
        $this->assertTrue($variable->isEntityDeleteAllowed());

        $variable->setEntityAcl(['update' => false, 'delete' => false]);
        $this->assertFalse($variable->isEntityUpdateAllowed());
        $this->assertFalse($variable->isEntityDeleteAllowed());

        $variable->setEntityAcl(['update' => true, 'delete' => true]);
        $this->assertTrue($variable->isEntityUpdateAllowed());
        $this->assertTrue($variable->isEntityDeleteAllowed());
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
