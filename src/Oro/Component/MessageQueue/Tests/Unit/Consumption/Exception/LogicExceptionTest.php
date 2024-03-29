<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\ExceptionInterface;
use Oro\Component\MessageQueue\Consumption\Exception\LogicException;
use Oro\Component\Testing\ClassExtensionTrait;

class LogicExceptionTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(ExceptionInterface::class, LogicException::class);
    }

    public function testShouldExtendLogicException()
    {
        $this->assertClassExtends(\LogicException::class, LogicException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new LogicException();
    }
}
