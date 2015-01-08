<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Entity;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ConfigValueTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new ConfigValue();

        $this->setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    public function testCreatedAtGetter()
    {
        $date = new \DateTime('now');

        $obj = new ConfigValue();
        $this->setCreatedAt($obj, $date);
        $this->assertEquals($date, $obj->getCreatedAt());
    }

    /**
     * @dataProvider propertiesDataProvider
     *
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new ConfigValue();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertSame($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
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
     *
     * @param mixed  $value
     * @param string $expectedType
     */
    public function testValueSettersAndGetters($value, $expectedType)
    {
        $obj = new ConfigValue();

        $obj->setValue($value);
        $this->assertEquals($value, $obj->getValue());
        $this->assertEquals($expectedType, $obj->getType());
    }

    public function valuesDataProvider()
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
        $this->assertInstanceOf('\DateTime', $obj->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $obj = new ConfigValue();

        $this->assertNull($obj->getUpdatedAt());

        $obj->doPreUpdate();
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    /**
     * @param mixed $obj
     * @param mixed $val
     */
    protected function setId($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }

    /**
     * @param mixed $obj
     * @param mixed $val
     */
    protected function setCreatedAt($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('createdAt');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }
}
