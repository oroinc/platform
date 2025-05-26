<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;
use PHPUnit\Framework\TestCase;

class InvalidFilterValueExceptionTest extends TestCase
{
    public function testShouldSetMessage(): void
    {
        $exception = new InvalidFilterValueException('some message');
        self::assertEquals('some message', $exception->getMessage());
    }
}
