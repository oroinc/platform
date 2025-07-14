<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Id;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use PHPUnit\Framework\TestCase;

class FieldConfigIdTest extends TestCase
{
    public function testFieldConfigId(): void
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

    public function testSerialize(): void
    {
        $fieldId = new FieldConfigId('testScope', 'Test\Class', 'testField', 'string');

        $this->assertEquals($fieldId, unserialize(serialize($fieldId)));
    }

    public function testSetState(): void
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
}
