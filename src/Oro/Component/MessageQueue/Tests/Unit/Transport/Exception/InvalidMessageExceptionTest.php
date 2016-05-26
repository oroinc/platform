<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Exception;

use Oro\Component\MessageQueue\Transport\Exception\Exception as ExceptionInterface;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\Testing\ClassExtensionTrait;

class InvalidMessageExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfException()
    {
        $this->assertClassExtends(ExceptionInterface::class, InvalidMessageException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new InvalidMessageException();
    }
}