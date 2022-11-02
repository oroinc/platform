<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;

class InvalidFilterValueExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSetMessage()
    {
        $exception = new InvalidFilterValueException('some message');
        self::assertEquals('some message', $exception->getMessage());
    }
}
