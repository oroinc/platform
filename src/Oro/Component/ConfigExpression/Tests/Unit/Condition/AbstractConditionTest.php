<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\Testing\ReflectionUtil;

class AbstractConditionTest extends \PHPUnit\Framework\TestCase
{
    public function testEvaluateForAllowedCondition()
    {
        $context = new \stdClass();
        $condition = $this->createCondition(true);
        $errors = new \ArrayObject();
        $result = $condition->evaluate($context);
        self::assertTrue($result);
        self::assertNull(ReflectionUtil::getPropertyValue($condition, 'errors'));
        self::assertCount(0, $errors);
    }

    public function testEvaluateForNotAllowedCondition()
    {
        $context = new \stdClass();
        $condition = $this->createCondition(false);
        $condition->setMessage('test_message');
        $errors = new \ArrayObject();
        $result = $condition->evaluate($context, $errors);
        self::assertFalse($result);
        self::assertNull(ReflectionUtil::getPropertyValue($condition, 'errors'));
        self::assertCount(1, $errors);
        self::assertEquals(['message' => 'test_message', 'parameters' => []], $errors[0]);
    }

    private function createCondition(bool $allowed): AbstractCondition
    {
        return new class($allowed) extends AbstractCondition {
            private bool $isConditionAllowed;

            public function __construct($isConditionAllowed)
            {
                $this->isConditionAllowed = $isConditionAllowed;
            }

            protected function isConditionAllowed($context)
            {
                return $this->isConditionAllowed;
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
}
