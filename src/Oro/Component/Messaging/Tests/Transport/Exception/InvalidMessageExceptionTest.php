<?php
namespace Oro\Component\Messaging\Tests\Transport\Exception;

use Oro\Component\Messaging\Transport\Exception\InvalidMessageException;
use Oro\Component\Testing\ClassExtensionTrait;

class InvalidMessageExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfException()
    {
        $this->assertClassExtends(
            'Oro\Component\Messaging\Transport\Exception\Exception',
            'Oro\Component\Messaging\Transport\Exception\InvalidMessageException'
        );
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new InvalidMessageException();
    }
}