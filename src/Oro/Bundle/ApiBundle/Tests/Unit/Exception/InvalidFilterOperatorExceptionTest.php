<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;

class InvalidFilterOperatorExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldBuildMessage()
    {
        $exception = new InvalidFilterOperatorException('test_operator');
        self::assertEquals('The operator "test_operator" is not supported.', $exception->getMessage());
    }

    public function testShouldSetOperator()
    {
        $exception = new InvalidFilterOperatorException('test_operator');
        self::assertEquals('test_operator', $exception->getOperator());
    }
}
