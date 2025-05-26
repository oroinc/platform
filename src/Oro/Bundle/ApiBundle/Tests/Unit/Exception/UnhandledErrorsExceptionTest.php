<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException;
use Oro\Bundle\ApiBundle\Model\Error;
use PHPUnit\Framework\TestCase;

class UnhandledErrorsExceptionTest extends TestCase
{
    public function testShouldSetMessage(): void
    {
        $exception = new UnhandledErrorsException([Error::create('test')]);
        self::assertEquals('Unhandled error(s) occurred.', $exception->getMessage());
    }

    public function testShouldSetErrors(): void
    {
        $errors = [Error::create('test')];
        $exception = new UnhandledErrorsException($errors);
        self::assertEquals($errors, $exception->getErrors());
    }
}
