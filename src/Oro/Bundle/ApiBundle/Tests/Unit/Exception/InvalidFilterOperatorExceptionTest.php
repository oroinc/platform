<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;
use PHPUnit\Framework\TestCase;

class InvalidFilterOperatorExceptionTest extends TestCase
{
    public function testShouldBuildMessage(): void
    {
        $exception = new InvalidFilterOperatorException('test_operator');
        self::assertEquals('The operator "test_operator" is not supported.', $exception->getMessage());
    }

    public function testShouldSetOperator(): void
    {
        $exception = new InvalidFilterOperatorException('test_operator');
        self::assertEquals('test_operator', $exception->getOperator());
    }
}
