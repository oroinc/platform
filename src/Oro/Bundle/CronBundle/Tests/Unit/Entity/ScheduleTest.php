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

    public function testConstructor()
    {
        $this->assertAttributes([]);
    }

    public function testGetId()
    {
        $this->assertNull($this->object->getId());

        $testValue = 42;
        $this->setProperty($this->object, 'id', $testValue);

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
        return [
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
        ];
    }

    public function testSetArguments()
    {
        $this->object->setArguments(['test' => 'value', 'some' => 'data']);

        $this->assertAttributes(['data', 'value']);
    }

    public function testGetHash()
    {
        $args = ['test' => 'value', 'some' => 'data'];
        $this->object->setArguments($args);

        sort($args);
        $this->assertSame(md5(json_encode($args)), $this->object->getArgumentsHash());
    }

    /**
     * @param array $attributes
     */
    protected function assertAttributes(array $attributes = [])
    {
        $this->assertAttributeEquals($attributes, 'arguments', $this->object);
        $this->assertAttributeEquals(md5(json_encode($attributes)), 'argumentsHash', $this->object);
    }

    public function testToString()
    {
        $testValue = 42;
        $this->setProperty($this->object, 'id', $testValue);

        $this->assertSame('42', (string)$this->object);
    }

    /**
     * @param Schedule $object
     * @param string $name
     * @param mixed $value
     */
    protected function setProperty(Schedule $object, $name, $value)
    {
        $reflectionProperty = new \ReflectionProperty('Oro\Bundle\CronBundle\Entity\Schedule', $name);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }
}
