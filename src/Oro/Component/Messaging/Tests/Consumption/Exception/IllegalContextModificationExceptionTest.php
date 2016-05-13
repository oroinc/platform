<?php
namespace Oro\Component\Messaging\Tests\Consumption;

use Oro\Component\Messaging\Consumption\Exception\Exception;
use Oro\Component\Messaging\Consumption\Exception\IllegalContextModificationException;
use Oro\Component\Testing\ClassExtensionTrait;

class IllegalContextModificationExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(Exception::class, IllegalContextModificationException::class);
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
