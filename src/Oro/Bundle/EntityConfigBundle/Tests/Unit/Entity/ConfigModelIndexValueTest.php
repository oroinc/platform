<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Testing\ReflectionUtil;

class ConfigModelIndexValueTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $obj = new ConfigModelIndexValue('testScope', 'testCode', 'testValue');
        $this->assertEquals('testScope', $obj->getScope());
        $this->assertEquals('testCode', $obj->getCode());
        $this->assertEquals('testValue', $obj->getValue());
    }

    public function testIdGetter()
    {
        $obj = new ConfigModelIndexValue();
        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new ConfigModelIndexValue();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['scope', 'testScope'],
            ['code', 'testCode'],
            ['value', 'testValue'],
            ['entity', new EntityConfigModel()],
            ['field', new FieldConfigModel()],
        ];
    }
}
