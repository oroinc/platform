<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidHeaderValueException;

class InvalidHeaderValueExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $exception = new InvalidHeaderValueException('some message');
        self::assertEquals('some message', $exception->getMessage());
    }
}
