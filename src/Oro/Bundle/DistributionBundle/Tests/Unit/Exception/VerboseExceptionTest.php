<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Exception;

use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;

class VerboseExceptionTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionHelperTrait;

    /**
     * @test
     */
    public function shouldBeSubclassOfException()
    {
        $this->assertSubclassOf('Exception', 'Oro\Bundle\DistributionBundle\Exception\VerboseException');
    }

    /**
     * @test
     */
    public function canBeConstructedWithoutArgs()
    {
        new VerboseException();
    }

    /**
     * @test
     */
    public function shouldReturnMessage()
    {
        $e = new VerboseException($message = uniqid());
        $this->assertEquals($message, $e->getMessage());
    }

    /**
     * @test
     */
    public function shouldReturnVerboseMessage()
    {
        $e = new VerboseException('message', $verboseMessage = uniqid());
        $this->assertEquals($verboseMessage, $e->getVerboseMessage());
    }

    /**
     * @test
     */
    public function shouldReturnCode()
    {
        $e = new VerboseException('message', 'verbose massage', $code = rand(1, 100));
        $this->assertEquals($code, $e->getCode());
    }

    /**
     * @test
     */
    public function shouldReturnPreviousException()
    {
        $e = new VerboseException('message', 'verbose massage', 0, $previousException = new \Exception);
        $this->assertSame($previousException, $e->getPrevious());
    }
}
