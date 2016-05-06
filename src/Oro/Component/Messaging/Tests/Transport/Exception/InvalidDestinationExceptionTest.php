<?php
namespace Oro\Component\Messaging\Tests\Transport\Exception;

use Oro\Component\Messaging\Transport\Destination;
use Oro\Component\Messaging\Transport\Exception\InvalidDestinationException;
use Oro\Component\Testing\ClassExtensionTrait;

// @codingStandardsIgnoreStart

class InvalidDestinationExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldBeSubClassOfException()
    {
        $this->assertClassExtends(
            'Oro\Component\Messaging\Transport\Exception\Exception',
            'Oro\Component\Messaging\Transport\Exception\InvalidDestinationException'
        );
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new InvalidDestinationException();
    }

    /**
     * @expectedException \Oro\Component\Messaging\Transport\Exception\InvalidDestinationException
     * @expectedExceptionMessage A destination is not understood. Destination must be an instance of Oro\Component\Messaging\Tests\Transport\Exception\DestinationBar but it is Oro\Component\Messaging\Tests\Transport\Exception\DestinationFoo.
     */
    public function testThrowIfAssertDestinationInstanceOfNotSameAsExpected()
    {
        InvalidDestinationException::assertDestinationInstanceOf(
            new DestinationFoo(),
            'Oro\Component\Messaging\Tests\Transport\Exception\DestinationBar'
        );
    }

    public function testShouldDoNothingIfAssertDestinationInstanceOfSameAsExpected()
    {
        InvalidDestinationException::assertDestinationInstanceOf(
            new DestinationFoo(),
            'Oro\Component\Messaging\Tests\Transport\Exception\DestinationFoo'
        );
    }
}

class DestinationFoo implements Destination
{
}

class DestinationBar implements Destination
{
}

// @codingStandardsIgnoreEnd