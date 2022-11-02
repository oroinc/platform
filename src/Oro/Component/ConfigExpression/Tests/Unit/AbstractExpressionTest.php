<?php
declare(strict_types=1);

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\AbstractExpression;
use Oro\Component\Testing\ReflectionUtil;

class AbstractExpressionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractExpression */
    private $condition;

    protected function setUp(): void
    {
        $this->condition = new class() extends AbstractExpression {
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
        self::assertSame($this->condition, $result);
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

        self::assertEquals([
            'message' => 'test message',
            'parameters' => [
                'x' => 'y',
                '{{ reason }}' => 'test reason'
            ]
        ], $errors[0]);
        self::assertNull(ReflectionUtil::getPropertyValue($this->condition, 'errors'));
        self::assertSame($context, $result);
    }

    public function testAddError()
    {
        $errors = new \ArrayObject();
        $this->condition->setMessage('test message');
        $context = ['test params' => ['x' => 'y']];

        $this->condition->evaluate($context, $errors);

        self::assertEquals([
            'message' => 'test message',
            'parameters' => [
                'x' => 'y',
                '{{ reason }}' => 'test reason'
            ]
        ], $errors[0]);
    }
}
