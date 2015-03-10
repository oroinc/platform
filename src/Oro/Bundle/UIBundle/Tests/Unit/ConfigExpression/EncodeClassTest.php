<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\ConfigExpression;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\PropertyAccess\PropertyPath;

use Oro\Bundle\UIBundle\ConfigExpression\EncodeClass;

class EncodeClassTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityRoutingHelper;

    /** @var EncodeClass */
    protected $function;

    protected function setUp()
    {
        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->function = new EncodeClass($this->entityRoutingHelper);
        $this->function->setContextAccessor(new ContextAccessor());
    }

    public function testEvaluate()
    {
        $options        = ['Test\Entity'];
        $context        = [];
        $expectedResult = 'Test_Entity';

        $this->entityRoutingHelper->expects($this->once())
            ->method('encodeClassName')
            ->with($options[0])
            ->will($this->returnValue($expectedResult));

        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function testEvaluateWithNullValue()
    {
        $options = [new PropertyPath('value')];
        $context = ['value' => null];

        $this->entityRoutingHelper->expects($this->never())
            ->method('encodeClassName');

        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertNull($this->function->evaluate($context));
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->function->initialize([]);
    }

    public function testAddErrorForInvalidValueType()
    {
        $context = ['value' => 123];
        $options = [new PropertyPath('value')];

        $this->function->initialize($options);
        $message = 'Error message.';
        $this->function->setMessage($message);

        $errors = new ArrayCollection();

        $this->entityRoutingHelper->expects($this->never())
            ->method('encodeClassName');

        $this->assertSame($context['value'], $this->function->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            [
                'message'    => $message,
                'parameters' => [
                    '{{ value }}'  => 123,
                    '{{ reason }}' => 'Expected a string value, got "integer".'
                ]
            ],
            $errors->get(0)
        );
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
                'options'  => ['value'],
                'message'  => null,
                'expected' => [
                    '@encode_class' => [
                        'parameters' => [
                            'value'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('context.entity_class')],
                'message'  => 'Test',
                'expected' => [
                    '@encode_class' => [
                        'message'    => 'Test',
                        'parameters' => [
                            '$context.entity_class'
                        ]
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
                'options'  => ['value'],
                'message'  => null,
                'expected' => '$factory->create(\'encode_class\', [\'value\'])'
            ],
            [
                'options'  => [new PropertyPath('context.entity_class')],
                'message'  => 'Test',
                'expected' => '$factory->create(\'encode_class\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath('
                    . '\'context.entity_class\', [\'context\', \'entity_class\'])'
                    . '])->setMessage(\'Test\')'
            ]
        ];
    }
}
