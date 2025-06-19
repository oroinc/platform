<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueKeyException;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use PHPUnit\Framework\TestCase;

class InvalidFilterValueKeyExceptionTest extends TestCase
{
    public function testShouldSetMessage(): void
    {
        $exception = new InvalidFilterValueKeyException('some message', new FilterValue('path', 'test'));
        self::assertEquals('some message', $exception->getMessage());
    }

    public function testShouldSetFilterValue(): void
    {
        $filterValue = new FilterValue('path', 'test');
        $exception = new InvalidFilterValueKeyException('some message', $filterValue);
        self::assertSame($filterValue, $exception->getFilterValue());
    }
}
