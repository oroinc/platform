<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\ResourceNotAccessibleException;

class ResourceNotAccessibleExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSetMessage()
    {
        $exception = new ResourceNotAccessibleException();
        self::assertEquals('The resource is not accessible.', $exception->getMessage());
    }

    public function testShouldSetCustomMessage()
    {
        $message = 'some message';
        $exception = new ResourceNotAccessibleException($message);
        self::assertEquals($message, $exception->getMessage());
    }
}
