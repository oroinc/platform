<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidSorterException;
use PHPUnit\Framework\TestCase;

class InvalidSorterExceptionTest extends TestCase
{
    public function testShouldSetMessage(): void
    {
        $exception = new InvalidSorterException('some message');
        self::assertEquals('some message', $exception->getMessage());
    }
}
