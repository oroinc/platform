<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Entity;

use Oro\Bundle\CronBundle\Entity\Schedule;

class ScheduleTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected function setUp(): void
    {
        $this->object = new class() extends Schedule {
            public function xsetId(int $id): void
            {
                $this->id = $id;
            }
        };
    }

    protected function tearDown(): void
    {
        unset($this->object);
    }

    public function testConstructorSetsDefaultArguments()
    {
        static::assertEquals([], $this->object->getArguments());
        static::assertEquals(\md5(\json_encode([])), $this->object->getArgumentsHash());
    }

    public function testGetId()
    {
        static::assertNull($this->object->getId());

        $testValue = 42;
        $this->object->xsetId($testValue);

        static::assertEquals($testValue, $this->object->getId());
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
        $setter = 'set' . \ucfirst($propertyName);
        $getter = 'get' . \ucfirst($propertyName);

        static::assertEquals($defaultValue, $this->object->$getter());
        static::assertSame($this->object, $this->object->$setter($testValue));
        static::assertSame($expectedValue !== null ? $expectedValue : $testValue, $this->object->$getter());
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
        $args = ['test' => 'value', 'some' => 'data'];
        $this->object->setArguments($args);

        sort($args);
        static::assertEquals($args, $this->object->getArguments());
        static::assertEquals(\md5(\json_encode($args)), $this->object->getArgumentsHash());
    }

    public function testGetHash()
    {
        $args = ['test' => 'value', 'some' => 'data'];
        $this->object->setArguments($args);

        sort($args);
        $this->assertSame(\md5(\json_encode($args)), $this->object->getArgumentsHash());
    }

    public function testToString()
    {
        $testValue = 42;
        $this->object->xsetId($testValue);

        static::assertSame('42', (string)$this->object);
    }
}
