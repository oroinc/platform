<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;

class InvalidFilterExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSetMessage()
    {
        $exception = new InvalidFilterException('some message');
        self::assertEquals('some message', $exception->getMessage());
    }
}
