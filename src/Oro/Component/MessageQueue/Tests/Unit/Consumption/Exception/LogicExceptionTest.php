<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\ExceptionInterface;
use Oro\Component\MessageQueue\Consumption\Exception\LogicException;
use Oro\Component\Testing\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class LogicExceptionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExceptionInterface(): void
    {
        $this->assertClassImplements(ExceptionInterface::class, LogicException::class);
    }

    public function testShouldExtendLogicException(): void
    {
        $this->assertClassExtends(\LogicException::class, LogicException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments(): void
    {
        new LogicException();
    }
}
