<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidHeaderValueException;
use PHPUnit\Framework\TestCase;

class InvalidHeaderValueExceptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new InvalidHeaderValueException('some message');
        self::assertEquals('some message', $exception->getMessage());
    }
}
