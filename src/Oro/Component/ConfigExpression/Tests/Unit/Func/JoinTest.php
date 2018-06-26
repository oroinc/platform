<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;
use Symfony\Component\PropertyAccess\PropertyPath;

class JoinTest extends \PHPUnit\Framework\TestCase
{
    /** @var Func\Join */
    protected $function;

    protected function setUp()
    {
        $this->function = new Func\Join();
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
            'one_value'      => [
                'options'        => [',', new PropertyPath('foo')],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => 'bar'
            ],
            'several_values' => [
                'options'        => [
                    new PropertyPath('glue'),
                    new PropertyPath('foo'),
                    'val2',
                    new PropertyPath('baz')
                ],
                'context'        => ['glue' => ',', 'foo' => 'bar', 'baz' => 'qux'],
                'expectedResult' => 'bar,val2,qux'
            ]
        ];
    }

    public function testEvaluateWithNonStringGlue()
    {
        $context = ['foo' => 'bar'];
        $options = [123, new PropertyPath('foo')];

        $errors = new ArrayCollection();

        $this->function->initialize($options)->setMessage('Error message.');
        $this->assertSame('', $this->function->evaluate($context, $errors));
    }

    public function testAddError()
    {
        $context = ['glue' => 123, 'foo' => 'bar', 'baz' => 'qux'];
        $options = [new PropertyPath('glue'), new PropertyPath('foo'), new PropertyPath('baz')];

        $this->function->initialize($options);
        $message = 'Error message.';
        $this->function->setMessage($message);

        $errors = new ArrayCollection();

        $this->assertSame('', $this->function->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            [
                'message'    => $message,
                'parameters' => [
                    '{{ glue }}'   => 123,
                    '{{ values }}' => 'bar, qux',
                    '{{ reason }}' => 'Expected a string value for the glue, got "integer".'
                ]
            ],
            $errors->get(0)
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have at least 2 elements, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->function->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have at least 2 elements, but 1 given.
     */
    public function testInitializeFailsWhenOnlyGlueSpecified()
    {
        $this->function->initialize([',']);
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
                'options'  => [',', 'test'],
                'message'  => null,
                'expected' => [
                    '@join' => [
                        'parameters' => [',', 'test']
                    ]
                ]
            ],
            [
                'options'  => [',', 'test1', 'test2'],
                'message'  => 'Test',
                'expected' => [
                    '@join' => [
                        'message'    => 'Test',
                        'parameters' => [',', 'test1', 'test2']
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
                'options'  => [',', 'test'],
                'message'  => null,
                'expected' => '$factory->create(\'join\', [\',\', \'test\'])'
            ],
            [
                'options'  => [',', 'test1', 'test2'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'join\', [\',\', \'test1\', \'test2\'])'
                    . '->setMessage(\'Test\')'
            ]
        ];
    }
}
