<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Id;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class FieldConfigIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyScope($scope)
    {
        new FieldConfigId($scope, 'Test\Class', 'testField', 'int');
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyClassName($className)
    {
        new FieldConfigId('testScope', $className, 'testField', 'int');
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyFieldName($fieldName)
    {
        new FieldConfigId('testScope', 'Test\Class', $fieldName, 'int');
    }

    public function testFieldConfigId()
    {
        $fieldId = new FieldConfigId('testScope', 'Test\Class', 'testField', 'string');

        $this->assertEquals('Test\Class', $fieldId->getClassName());
        $this->assertEquals('testScope', $fieldId->getScope());
        $this->assertEquals('testField', $fieldId->getFieldName());
        $this->assertEquals('string', $fieldId->getFieldType());
        $this->assertEquals('field_testScope_Test-Class_testField', $fieldId->toString());

        $fieldId->setFieldType('integer');
        $this->assertEquals('integer', $fieldId->getFieldType());
    }

    public function testSerialize()
    {
        $fieldId = new FieldConfigId('testScope', 'Test\Class', 'testField', 'string');

        $this->assertEquals($fieldId, unserialize(serialize($fieldId)));
    }

    public function testSetState()
    {
        $fieldId = FieldConfigId::__set_state(
            [
                'className' => 'Test\Class',
                'scope' => 'testScope',
                'fieldName' => 'testField',
                'fieldType' => 'string',
            ]
        );
        $this->assertEquals('Test\Class', $fieldId->getClassName());
        $this->assertEquals('testScope', $fieldId->getScope());
        $this->assertEquals('testField', $fieldId->getFieldName());
        $this->assertEquals('string', $fieldId->getFieldType());
    }

    public function emptyNameProvider()
    {
        return [
            [null],
            [''],
        ];
    }
}
