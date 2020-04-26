<?php
declare(strict_types=1);

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\AbstractExpression;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractExpressionTest extends TestCase
{
    /** @var AbstractExpression|MockObject */
    private $condition;

    protected function setUp(): void
    {
        $this->condition = new class() extends AbstractExpression {
            public function xgetErrors(): ?\ArrayAccess
            {
                return $this->errors;
            }

            protected function doEvaluate($context)
            {
                $this->addError($context, 'test reason');

                return $context;
            }

            protected function getMessageParameters($context)
            {
                return $context['test params'];
            }

            public function getName()
            {
            }

            public function initialize(array $options)
            {
            }

            public function toArray()
            {
            }

            public function compile($factoryAccessor)
            {
            }
        };
    }

    public function testSetMessage()
    {
        $result = $this->condition->setMessage('Test Message');
        static::assertSame($this->condition, $result);
    }

    public function testEvaluate()
    {
        // check that evaluate:
        // 1) allows something to be done with the passed errors object but then un-attaches itself from it
        // 2) passed the context to doEvaluate
        // 3) passes through the return of doEvaluate
        $errors = new \ArrayObject();
        $context = ['test params' => ['x' => 'y']];
        $this->condition->setMessage('test message');

        $result = $this->condition->evaluate($context, $errors);

        static::assertEquals([
            'message' => 'test message',
            'parameters' => [
                'x' => 'y',
                '{{ reason }}' => 'test reason'
            ]
        ], $errors[0]);
        static::assertNull($this->condition->xgetErrors());
        static::assertSame($context, $result);
    }

    public function testAddError()
    {
        $errors = new \ArrayObject();
        $this->condition->setMessage('test message');
        $context = ['test params' => ['x' => 'y']];

        $this->condition->evaluate($context, $errors);

        static::assertEquals([
            'message' => 'test message',
            'parameters' => [
                'x' => 'y',
                '{{ reason }}' => 'test reason'
            ]
        ], $errors[0]);
    }
}
