<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\CopyValues;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class CopyValuesTest extends \PHPUnit\Framework\TestCase
{
    /** @var CopyValues */
    protected $action;

    protected function setUp(): void
    {
        $this->action = new class(new ContextAccessor()) extends CopyValues {
            public function xgetAttribute(): PropertyPathInterface
            {
                return $this->attribute;
            }

            public function xgetOptions(): array
            {
                return $this->options;
            }
        };
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    public function testInitialize()
    {
        $this->action->initialize([
            new PropertyPath('attr'),
            new PropertyPath('prop1'),
            []
        ]);

        static::assertEquals(new PropertyPath('attr'), $this->action->xgetAttribute());
        static::assertEquals([new PropertyPath('prop1'), []], $this->action->xgetOptions());
    }

    public function testInitializeWithEmptyOptions()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute and data parameters are required');
        $this->action->initialize([]);
    }

    public function testInitializeWithIncorrectAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition');
        $this->action->initialize(['var1', 'var2']);
    }

    /**
     * @dataProvider executeProvider
     */
    public function testExecute(array $inputData, array $options, array $expectedData)
    {
        $context = new ItemStub($inputData);

        $this->action->initialize($options);
        $this->action->execute($context);

        static::assertEquals($expectedData, $context->getData());
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        return [
            'object attribute' => [
                'input' => [
                    'attr' => (object)['key1' => 'value1', 'key2' => null, 'key3' => null],
                    'prop' => ['key2' => 'value2'],
                    'ignored_prop' => (object)['key4' => 'value4'],
                ],
                'options' => [
                    new PropertyPath('attr'),
                    new PropertyPath('prop'),
                    new PropertyPath('ignored_prop'),
                    ['key3' => 'value3'],
                ],
                'expected' => [
                    'attr' => (object)['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                    'prop' => ['key2' => 'value2'],
                    'ignored_prop' => (object)['key4' => 'value4'],
                ],
            ],
            'array attribute' => [
                'input' => [
                    'attr' => ['key1' => 'value1'],
                    'prop' => ['key2' => 'value2'],
                    'ignored_prop' => 'property value',
                ],
                'options' => [
                    new PropertyPath('attr'),
                    new PropertyPath('prop'),
                    new PropertyPath('ignored_prop'),
                    ['key3' => 'value3'],
                ],
                'expected' => [
                    'attr' => ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                    'prop' => ['key2' => 'value2'],
                    'ignored_prop' => 'property value',
                ],
            ],
            'null attribute' => [
                'input' => [
                    'prop' => ['key2' => 'value2'],
                    'ignored_prop' => 123,
                ],
                'options' => [
                    new PropertyPath('attr'),
                    new PropertyPath('prop'),
                    new PropertyPath('ignored_prop'),
                    ['key3' => 'value3'],
                ],
                'expected' => [
                    'attr' => ['key2' => 'value2', 'key3' => 'value3'],
                    'prop' => ['key2' => 'value2'],
                    'ignored_prop' => 123,
                ],
            ],
        ];
    }
}
