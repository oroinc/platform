<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition;

class AbstractConditionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Condition\AbstractCondition|\PHPUnit\Framework\MockObject\MockObject */
    protected $condition;

    protected function setUp()
    {
        $this->condition = $this->getMockBuilder('Oro\Component\ConfigExpression\Condition\AbstractCondition')
            ->setMethods(['isConditionAllowed'])
            ->getMockForAbstractClass();
    }

    public function testMessages()
    {
        $this->assertAttributeSame(null, 'message', $this->condition);
        $this->assertSame($this->condition, $this->condition->setMessage('Test'));
        $this->assertAttributeEquals('Test', 'message', $this->condition);
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate($allowed, $message, $expectMessage = false)
    {
        $errorMessage = 'Some error message';
        $context      = ['key' => 'value'];

        $this->condition->expects($this->any())
            ->method('isConditionAllowed')
            ->with($context)
            ->will($this->returnValue($allowed));

        if ($message) {
            $this->condition->setMessage($errorMessage);
        }

        // without message collection
        $this->assertEquals($allowed, $this->condition->evaluate($context));

        // with message collection
        $errors = new ArrayCollection();
        $this->assertEquals($allowed, $this->condition->evaluate($context, $errors));
        if ($expectMessage) {
            $this->assertCount(1, $errors);
            $this->assertEquals(
                ['message' => $errorMessage, 'parameters' => []],
                $errors->get(0)
            );
        } else {
            $this->assertEmpty($errors->getValues());
        }
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            'allowed, no error message'       => [
                'allowed' => true,
                'message' => false,
            ],
            'not allowed, no error message'   => [
                'allowed' => false,
                'message' => false,
            ],
            'allowed, with error message'     => [
                'allowed' => true,
                'message' => true,
            ],
            'not allowed, with error message' => [
                'allowed'       => false,
                'message'       => true,
                'expectMessage' => true,
            ]
        ];
    }

    public function testAddError()
    {
        $context = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $options = ['left' => 'foo', 'right' => 'bar'];

        $this->condition->initialize($options);
        $message = 'Error message.';
        $this->condition->setMessage($message);

        $this->condition->expects($this->once())
            ->method('isConditionAllowed')
            ->with($context)
            ->will($this->returnValue(false));

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(['message' => $message, 'parameters' => []], $errors->get(0));
    }
}
