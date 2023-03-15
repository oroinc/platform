<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Entity;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Testing\ReflectionUtil;

class ConfigValueTest extends \PHPUnit\Framework\TestCase
{
    public function testIdGetter()
    {
        $obj = new ConfigValue();

        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    public function testCreatedAtGetter()
    {
        $date = new \DateTime('now');

        $obj = new ConfigValue();
        ReflectionUtil::setPropertyValue($obj, 'createdAt', $date);
        $this->assertEquals($date, $obj->getCreatedAt());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new ConfigValue();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertSame($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['name', 'testName'],
            ['config', new Config()],
            ['section', 'testSection'],
            ['type', ConfigValue::FIELD_ARRAY_TYPE],
            ['updatedAt', new \DateTime()],
        ];
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testValueSettersAndGetters(mixed $value, string $expectedType)
    {
        $obj = new ConfigValue();

        $obj->setValue($value);
        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($expectedType, $obj->getType());
    }

    public function valuesDataProvider(): array
    {
        return [
            ['string', ConfigValue::FIELD_SCALAR_TYPE],
            [123, ConfigValue::FIELD_SCALAR_TYPE],
            [new \stdClass(), ConfigValue::FIELD_OBJECT_TYPE],
            [[1, 2], ConfigValue::FIELD_ARRAY_TYPE],
        ];
    }

    public function testPrePersist()
    {
        $obj = new ConfigValue();

        $this->assertNull($obj->getCreatedAt());
        $this->assertNull($obj->getUpdatedAt());

        $obj->beforeSave();
        $this->assertInstanceOf(\DateTime::class, $obj->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $obj->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $obj = new ConfigValue();

        $this->assertNull($obj->getUpdatedAt());

        $obj->doPreUpdate();
        $this->assertInstanceOf(\DateTime::class, $obj->getUpdatedAt());
    }
}
