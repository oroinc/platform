<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Entity;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Component\Testing\ReflectionUtil;

class ScheduleTest extends \PHPUnit\Framework\TestCase
{
    private Schedule $object;

    protected function setUp(): void
    {
        $this->object = new Schedule();
    }

    public function testConstructorSetsDefaultArguments()
    {
        self::assertEquals([], $this->object->getArguments());
        self::assertEquals(\md5(\json_encode([])), $this->object->getArgumentsHash());
    }

    public function testGetId()
    {
        self::assertNull($this->object->getId());

        $testValue = 42;
        ReflectionUtil::setId($this->object, $testValue);

        self::assertEquals($testValue, $this->object->getId());
    }

    /**
     * @dataProvider setGetDataProvider
     */
    public function testSetGetEntity(
        string $propertyName,
        mixed $testValue,
        mixed $defaultValue = null,
        mixed $expectedValue = null
    ) {
        $setter = 'set' . \ucfirst($propertyName);
        $getter = 'get' . \ucfirst($propertyName);

        self::assertEquals($defaultValue, $this->object->$getter());
        self::assertSame($this->object, $this->object->$setter($testValue));
        self::assertSame($expectedValue ?? $testValue, $this->object->$getter());
    }

    public function setGetDataProvider(): array
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
        self::assertEquals($args, $this->object->getArguments());
        self::assertEquals(\md5(\json_encode($args)), $this->object->getArgumentsHash());
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
        ReflectionUtil::setId($this->object, 42);

        self::assertSame('42', (string)$this->object);
    }
}
