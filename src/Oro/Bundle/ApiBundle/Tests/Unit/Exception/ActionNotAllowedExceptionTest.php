<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ActionNotAllowedExceptionTest extends TestCase
{
    public function testShouldSetMessage(): void
    {
        $exception = new ActionNotAllowedException();
        self::assertEquals('The action is not allowed.', $exception->getMessage());
    }

    public function testShouldSetCustomMessage(): void
    {
        $message = 'some message';
        $exception = new ActionNotAllowedException($message);
        self::assertEquals($message, $exception->getMessage());
    }

    public function testShouldSetStatusCode(): void
    {
        $exception = new ActionNotAllowedException();
        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $exception->getStatusCode());
    }
}
