<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Oro\Bundle\EntityBundle\ORM\SingleObjectIterableResultDecorator;

class SingleObjectIterableResultDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var IterableResult|\PHPUnit_Framework_MockObject_MockObject */
    protected $iterableResult;

    /** @var SingleObjectIterableResultDecorator */
    protected $iterableResultDecorator;

    public function setUp()
    {
        $this->iterableResult = $this->getMockBuilder('Doctrine\ORM\Internal\Hydration\IterableResult')
            ->disableOriginalConstructor()
            ->getMock();

        $this->iterableResultDecorator = new SingleObjectIterableResultDecorator($this->iterableResult);
    }

    public function testRewind()
    {
        $this->iterableResult
            ->expects($this->once())
            ->method('rewind');

        $this->iterableResultDecorator->rewind();
    }

    public function testNextWhenArrayIsReturned()
    {
        $sampleObject = new \stdClass();

        $this->iterableResult
            ->expects($this->once())
            ->method('next')
            ->willReturn([$sampleObject]);

        $this->assertEquals($sampleObject, $this->iterableResultDecorator->next());
    }

    public function testNextWhenNotArrayIsReturned()
    {
        $this->iterableResult
            ->expects($this->once())
            ->method('next')
            ->willReturn(false);

        $this->assertEquals(false, $this->iterableResultDecorator->next());
    }

    public function testCurrent()
    {
        $sampleObject = new \stdClass();

        $this->iterableResult
            ->expects($this->once())
            ->method('current')
            ->willReturn([$sampleObject]);

        $this->assertEquals($sampleObject, $this->iterableResultDecorator->current());
    }

    public function testKey()
    {
        $this->iterableResult
            ->expects($this->once())
            ->method('key')
            ->willReturn('sample_key');

        $this->assertEquals('sample_key', $this->iterableResultDecorator->key());
    }

    public function testValid()
    {
        $this->iterableResult
            ->expects($this->once())
            ->method('valid')
            ->willReturn(true);

        $this->assertEquals(true, $this->iterableResultDecorator->valid());
    }
}
