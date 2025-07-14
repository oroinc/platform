<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\ConsumptionInterruptedException;
use Oro\Component\MessageQueue\Consumption\Exception\ExceptionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class ConsumptionInterruptedExceptionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExceptionInterface(): void
    {
        $this->assertClassImplements(ExceptionInterface::class, ConsumptionInterruptedException::class);
    }

    public function testShouldExtendLogicException(): void
    {
        $this->assertClassExtends(\LogicException::class, ConsumptionInterruptedException::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments(): void
    {
        new ConsumptionInterruptedException();
    }
}
