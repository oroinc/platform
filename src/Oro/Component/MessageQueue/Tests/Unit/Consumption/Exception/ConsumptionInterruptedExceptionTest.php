<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\Exception\ConsumptionInterruptedException;
use Oro\Component\MessageQueue\Consumption\Exception\ExceptionInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class ConsumptionInterruptedExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(ExceptionInterface::class, ConsumptionInterruptedException::class);
    }

    public function testShouldExtendLogicException()
    {
        $this->assertClassExtends(\LogicException::class, ConsumptionInterruptedException::class);
    }
    
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new ConsumptionInterruptedException();
    }
}
