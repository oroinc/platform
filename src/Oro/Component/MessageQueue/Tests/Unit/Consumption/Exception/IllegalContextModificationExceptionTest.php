<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\ExceptionInterface;
use Oro\Component\MessageQueue\Consumption\Exception\IllegalContextModificationException;
use Oro\Component\Testing\ClassExtensionTrait;

class IllegalContextModificationExceptionTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(ExceptionInterface::class, IllegalContextModificationException::class);
    }

    public function testShouldExtendLogicException()
    {
        $this->assertClassExtends(\LogicException::class, IllegalContextModificationException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new IllegalContextModificationException();
    }
}
