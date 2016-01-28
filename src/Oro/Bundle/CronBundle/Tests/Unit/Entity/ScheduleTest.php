<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Entity;

use Oro\Bundle\CronBundle\Entity\Schedule;

class ScheduleTest extends \PHPUnit_Framework_TestCase
{
    /** @var Schedule */
    protected $object;

    protected function setUp()
    {
        $this->object = new Schedule();
    }

    protected function tearDown()
    {
        unset($this->object);
    }

    public function testGetId()
    {
        $this->assertNull($this->object->getId());

        $testValue = 42;
        $reflectionProperty = new \ReflectionProperty('Oro\Bundle\CronBundle\Entity\Schedule', 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->object, $testValue);

        $this->assertEquals($testValue, $this->object->getId());
    }

    /**
     * @dataProvider setGetDataProvider
     *
     * @param string $propertyName
     * @param mixed $testValue
     * @param mixed $defaultValue
     * @param mixed $expectedValue
     */
    public function testSetGetEntity($propertyName, $testValue, $defaultValue = null, $expectedValue = null)
    {
        $setter = 'set' . ucfirst($propertyName);
        $getter = 'get' . ucfirst($propertyName);

        $this->assertEquals($defaultValue, $this->object->$getter());
        $this->assertSame($this->object, $this->object->$setter($testValue));
        $this->assertSame($expectedValue !== null ? $expectedValue : $testValue, $this->object->$getter());
    }

    /**
     * @return array
     */
    public function setGetDataProvider()
    {
        return array(
            'command' => [
                'propertyName' => 'command',
                'testValue' => 'oro:test'
            ],
            'arguments' => [
                'propertyName' => 'arguments',
                'testValue' => ['test' => 'value', 'some' => 'data'],
                'defaultValue' => [],
                'expectedValue' => ['data', 'value'],
            ],
            'definition' => [
                'propertyName' => 'definition',
                'testValue' => '*/5 * * * *'
            ]
        );
    }
}
