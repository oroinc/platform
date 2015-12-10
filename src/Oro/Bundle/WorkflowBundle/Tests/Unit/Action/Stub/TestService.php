<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Action\Stub;

class TestService
{
    const TEST_METHOD_RESULT = 'test_method_result';
    /**
     * @param mixed $value
     * @return string
     */
    public function testMethod($value)
    {
        return self::TEST_METHOD_RESULT . $value;
    }
}
