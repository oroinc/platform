<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidSorterException;

class InvalidSorterExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSetMessage()
    {
        $exception = new InvalidSorterException('some message');
        self::assertEquals('some message', $exception->getMessage());
    }
}
