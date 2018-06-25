<?php

namespace Oro\Bundle\FormBundle\Tests\Unit;

class MockHelper
{
    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $mock
     * @param array $expectedCalls
     * @param object|null $callbacksContext
     */
    public static function addMockExpectedCalls(
        \PHPUnit\Framework\MockObject\MockObject $mock,
        array $expectedCalls,
        $callbacksContext = null
    ) {
        $index = 0;
        if ($expectedCalls) {
            foreach ($expectedCalls as $expectedCall) {
                list($method, $arguments, $result) = $expectedCall;

                $methodExpectation = $mock->expects(\PHPUnit\Framework\TestCase::at($index++))->method($method);
                $methodExpectation = call_user_func_array(array($methodExpectation, 'with'), $arguments);
                if (is_string($result) && $callbacksContext && method_exists($callbacksContext, $result)) {
                    $result = $callbacksContext->$result();
                }
                $methodExpectation->will(\PHPUnit\Framework\TestCase::returnValue($result));
            }
        } else {
            $mock->expects(\PHPUnit\Framework\TestCase::never())->method(\PHPUnit\Framework\TestCase::anything());
        }
    }
}
