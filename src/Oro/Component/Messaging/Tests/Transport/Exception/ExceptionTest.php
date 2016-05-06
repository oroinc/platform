<?php
namespace Oro\Component\Messaging\Tests\Transport\Exception;

use Oro\Component\Messaging\Transport\Exception\Exception;
use Oro\Component\Testing\ClassExtensionTrait;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldBeSubClassOfException()
    {
        $this->assertClassExtends(
            'Exception',
            'Oro\Component\Messaging\Transport\Exception\Exception'
        );
    }

    public function testShouldImplementExceptionInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Exception',
            'Oro\Component\Messaging\Transport\Exception\Exception'
        );
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new Exception();
    }
}
