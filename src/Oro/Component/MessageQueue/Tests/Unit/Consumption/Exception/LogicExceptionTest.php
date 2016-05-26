<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\Exception\Exception;
use Oro\Component\MessageQueue\Consumption\Exception\LogicException;
use Oro\Component\Testing\ClassExtensionTrait;

class LogicExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(Exception::class, LogicException::class);
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
