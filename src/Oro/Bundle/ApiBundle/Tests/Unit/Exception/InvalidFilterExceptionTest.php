<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use PHPUnit\Framework\TestCase;

class InvalidFilterExceptionTest extends TestCase
{
    public function testShouldSetMessage(): void
    {
        $exception = new InvalidFilterException('some message');
        self::assertEquals('some message', $exception->getMessage());
    }
}
