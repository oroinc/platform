<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Entity;

use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;

class PermissionEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var PermissionEntity */
    protected $object;

    protected function setUp()
    {
        $this->object = new PermissionEntity();
    }

    protected function tearDown()
    {
        unset($this->object);
    }

    public function testGetId()
    {
        $this->assertNull($this->object->getId());

        $testValue = 42;
        $reflectionProperty = new \ReflectionProperty('Oro\Bundle\SecurityBundle\Entity\PermissionEntity', 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->object, $testValue);

        $this->assertEquals($testValue, $this->object->getId());
    }

    /**
     * @dataProvider setGetDataProvider
     *
     * @param string $propertyName
     * @param mixed $value
     * @param mixed $defaultValue
     */
    public function testSetGet($propertyName, $value, $defaultValue = null)
    {
        $setter = 'set' . ucfirst($propertyName);
        $getter = 'get' . ucfirst($propertyName);
        if (!method_exists($this->object, $getter)) {
            $getter = 'is' . ucfirst($propertyName);
        }

        $this->assertEquals($defaultValue, $this->object->$getter());
        $this->assertSame($this->object, $this->object->$setter($value));
        $this->assertSame($value, $this->object->$getter());
    }

    /**
     * @return array
     */
    public function setGetDataProvider()
    {
        return [
            'name' => [
                'propertyName' => 'name',
                'value' => 'test name',
            ],
        ];
    }
}
