<?php
namespace Oro\Component\Messaging\Tests\Consumption;

use Oro\Component\Messaging\Consumption\Exception\Exception;
use Oro\Component\Messaging\Consumption\Exception\LogicException;
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
