<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\ExceptionInterface;
use Oro\Component\MessageQueue\Consumption\Exception\IllegalContextModificationException;
use Oro\Component\Testing\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class IllegalContextModificationExceptionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExceptionInterface(): void
    {
        $this->assertClassImplements(ExceptionInterface::class, IllegalContextModificationException::class);
    }

    public function testShouldExtendLogicException(): void
    {
        $this->assertClassExtends(\LogicException::class, IllegalContextModificationException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments(): void
    {
        new IllegalContextModificationException();
    }
}
