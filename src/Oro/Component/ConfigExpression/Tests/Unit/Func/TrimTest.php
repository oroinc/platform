<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Func;
use Symfony\Component\PropertyAccess\PropertyPath;

class TrimTest extends \PHPUnit\Framework\TestCase
{
    /** @var Func\Trim */
    private $function;

    protected function setUp(): void
    {
        $this->function = new Func\Trim();
        $this->function->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, array $context, string $expectedResult)
    {
        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function evaluateDataProvider(): array
    {
        return [
            'nothing_to_trim'             => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => 'bar'
            ],
            'trim_spaces'                 => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => '  bar  '],
                'expectedResult' => 'bar'
            ],
            'trim_spaces_in_constant'     => [
                'options'        => ['  bar  '],
                'context'        => [],
                'expectedResult' => 'bar'
            ],
            'trim_underscores_and_spaces' => [
                'options'        => [new PropertyPath('foo'), '_ '],
                'context'        => ['foo' => '_ _bar_ _'],
                'expectedResult' => 'bar'
            ]
        ];
    }

    public function testEvaluateNullValue()
    {
        $context = ['foo' => null];
        $options = [new PropertyPath('foo')];

        $errors = new ArrayCollection();

        $this->function->initialize($options)->setMessage('Error message.');
        $this->assertNull($this->function->evaluate($context, $errors));
        $this->assertCount(0, $errors, 'An error should not be added');
    }

    public function testAddError()
    {
        $context = ['foo' => 123, 'charlist' => '_'];
        $options = [new PropertyPath('foo'), new PropertyPath('charlist')];

        $this->function->initialize($options);
        $message = 'Error message.';
        $this->function->setMessage($message);

        $errors = new ArrayCollection();

        $this->assertSame(123, $this->function->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            [
                'message'    => $message,
                'parameters' => [
                    '{{ value }}'    => 123,
                    '{{ charlist }}' => '_',
                    '{{ reason }}'   => 'Expected a string value, got "integer".'
                ]
            ],
            $errors->get(0)
        );
    }

    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 or 2 elements, but 0 given.');

        $this->function->initialize([]);
    }

    public function testInitializeFailsWhenMoreThanTwoOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 or 2 elements, but 3 given.');

        $this->function->initialize([1, 2, 3]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, ?string $message, array $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'options'  => ['test'],
                'message'  => null,
                'expected' => [
                    '@trim' => [
                        'parameters' => ['test']
                    ]
                ]
            ],
            [
                'options'  => ['test', '_'],
                'message'  => 'Test',
                'expected' => [
                    '@trim' => [
                        'message'    => 'Test',
                        'parameters' => ['test', '_']
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(array $options, ?string $message, string $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'options'  => ['test'],
                'message'  => null,
                'expected' => '$factory->create(\'trim\', [\'test\'])'
            ],
            [
                'options'  => ['test', '_'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'trim\', [\'test\', \'_\'])'
                    . '->setMessage(\'Test\')'
            ]
        ];
    }
}
