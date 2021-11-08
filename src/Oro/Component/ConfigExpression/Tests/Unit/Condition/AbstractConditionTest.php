<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;

class AbstractConditionTest extends \PHPUnit\Framework\TestCase
{
    public function testDoEvaluate()
    {
        $allowedCondition = new class() extends AbstractCondition {
            protected function isConditionAllowed($context)
            {
                return true;
            }

            public function xdoEvaluate($context)
            {
                return parent::doEvaluate($context);
            }

            public function xgetErrors(): ?\ArrayAccess
            {
                return $this->errors;
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

        $notAllowedCondition = new class() extends AbstractCondition {
            public function __construct()
            {
                $this->errors = new \ArrayObject();
            }

            protected function isConditionAllowed($context)
            {
                return false;
            }

            public function xdoEvaluate($context)
            {
                return parent::doEvaluate($context);
            }

            public function xgetErrors(): ?\ArrayAccess
            {
                return $this->errors;
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

        $context = new \stdClass();

        $result = $allowedCondition->xdoEvaluate($context);
        self::assertTrue($result);
        self::assertNull($allowedCondition->xgetErrors());

        $notAllowedCondition->setMessage('test_message');
        $result = $notAllowedCondition->xdoEvaluate($context);
        self::assertFalse($result);
        self::assertEquals(
            new \ArrayObject([['message' => 'test_message', 'parameters' => []]]),
            $notAllowedCondition->xgetErrors()
        );
    }
}
