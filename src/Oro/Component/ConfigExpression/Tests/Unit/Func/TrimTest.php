<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;

class TrimTest extends \PHPUnit_Framework_TestCase
{
    /** @var Func\Trim */
    protected $function;

    protected function setUp()
    {
        $this->function = new Func\Trim();
        $this->function->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, $context, $expectedResult)
    {
        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function evaluateDataProvider()
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

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 or 2 elements, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->function->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 or 2 elements, but 3 given.
     */
    public function testInitializeFailsWhenMoreThanTwoOptions()
    {
        $this->function->initialize([1, 2, 3]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($options, $message, $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider()
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
    public function testCompile($options, $message, $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider()
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
