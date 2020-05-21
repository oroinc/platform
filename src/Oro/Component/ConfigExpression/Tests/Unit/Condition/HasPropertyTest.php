<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\PropertyAccess\PropertyPath;

class HasPropertyTest extends \PHPUnit\Framework\TestCase
{
    /** @var Condition\HasProperty */
    protected $condition;

    protected function setUp(): void
    {
        $this->condition = new Condition\HasProperty();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals('has_property', $this->condition->getName());
    }

    public function testEvaluate()
    {
        $options = [new PropertyPath('object'), new PropertyPath('property')];
        $object = $this->createObject(['foo' => 'fooValue']);
        $this->condition->initialize($options);
        $this->assertTrue($this->condition->evaluate(['object' => $object, 'property' => 'foo']));
    }

    public function testEvaluateWithErrors()
    {
        $options = [new PropertyPath('object'), new PropertyPath('property')];
        $object = new \stdClass();
        $this->condition->initialize($options);
        $this->assertFalse($this->condition->evaluate(['object' => $object, 'property' => 'foo']));
    }

    public function testInitializeFailsWhenOptionOneNotDefined()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "object" is required.');

        $this->condition->initialize([2 => 'anything', 3 => 'anything']);
    }

    public function testInitializeFailsWhenOptionTwoNotDefined()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "property" is required.');

        $this->condition->initialize([0 => 'anything', 3 => 'anything']);
    }

    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 2 elements, but 0 given.');

        $this->condition->initialize([]);
    }

    public function testToArray()
    {
        $options = ['one', 'two'];
        $expected = [
            '@has_property' => [
                'parameters' => [
                    'one', 'two'
                ]
            ]
        ];
        $this->condition->initialize($options);
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function testCompile()
    {
        $result = $this->condition->compile('$factoryAccessor');

        static::assertStringContainsString('$factoryAccessor->create(\'has_property\'', $result);
    }

    /**
     * @param array $data
     *
     * @return ItemStub
     */
    protected function createObject(array $data = [])
    {
        return new ItemStub($data);
    }
}
