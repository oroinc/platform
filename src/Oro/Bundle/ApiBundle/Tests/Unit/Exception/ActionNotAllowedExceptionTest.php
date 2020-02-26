<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;

class ActionNotAllowedExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSetMessage()
    {
        $exception = new ActionNotAllowedException();
        self::assertEquals('The action is not allowed.', $exception->getMessage());
    }

    public function testShouldSetStatusCode()
    {
        $exception = new ActionNotAllowedException();
        self::assertSame(405, $exception->getStatusCode());
    }
}
