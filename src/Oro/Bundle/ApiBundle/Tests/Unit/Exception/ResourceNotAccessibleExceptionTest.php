<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\ResourceNotAccessibleException;
use PHPUnit\Framework\TestCase;

class ResourceNotAccessibleExceptionTest extends TestCase
{
    public function testShouldSetMessage(): void
    {
        $exception = new ResourceNotAccessibleException();
        self::assertEquals('The resource is not accessible.', $exception->getMessage());
    }

    public function testShouldSetCustomMessage(): void
    {
        $message = 'some message';
        $exception = new ResourceNotAccessibleException($message);
        self::assertEquals($message, $exception->getMessage());
    }
}
