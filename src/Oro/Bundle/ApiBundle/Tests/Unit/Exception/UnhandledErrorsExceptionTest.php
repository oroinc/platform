<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException;
use Oro\Bundle\ApiBundle\Model\Error;

class UnhandledErrorsExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSetMessage()
    {
        $exception = new UnhandledErrorsException([Error::create('test')]);
        self::assertEquals('Unhandled error(s) occurred.', $exception->getMessage());
    }

    public function testShouldSetErrors()
    {
        $errors = [Error::create('test')];
        $exception = new UnhandledErrorsException($errors);
        self::assertEquals($errors, $exception->getErrors());
    }
}
