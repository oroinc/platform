<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Oro\Bundle\EntityBundle\ORM\SingleObjectIterableResultDecorator;

class SingleObjectIterableResultDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var IterableResult|\PHPUnit\Framework\MockObject\MockObject */
    private $iterableResult;

    /** @var SingleObjectIterableResultDecorator */
    private $iterableResultDecorator;

    protected function setUp(): void
    {
        $this->iterableResult = $this->createMock(IterableResult::class);

        $this->iterableResultDecorator = new SingleObjectIterableResultDecorator($this->iterableResult);
    }

    public function testRewind()
    {
        $this->iterableResult->expects($this->once())
            ->method('rewind');

        $this->iterableResultDecorator->rewind();
    }

    public function testNextWhenArrayIsReturned()
    {
        $sampleObject = new \stdClass();

        $this->iterableResult->expects($this->once())
            ->method('next');
        $this->iterableResult->expects($this->once())
            ->method('current')
            ->willReturn([$sampleObject]);
        $this->iterableResultDecorator->next();
        $this->assertEquals($sampleObject, $this->iterableResultDecorator->current());
    }

    public function testNextWhenNotArrayIsReturned()
    {
        $this->iterableResult->expects($this->once())
            ->method('next');
        $this->iterableResult->expects($this->once())
            ->method('current')
            ->willReturn(false);
        $this->iterableResultDecorator->next();
        $this->assertEquals(false, $this->iterableResultDecorator->current());
    }

    public function testCurrent()
    {
        $sampleObject = new \stdClass();

        $this->iterableResult->expects($this->once())
            ->method('current')
            ->willReturn([$sampleObject]);

        $this->assertEquals($sampleObject, $this->iterableResultDecorator->current());
    }

    public function testKey()
    {
        $this->iterableResult->expects($this->once())
            ->method('key')
            ->willReturn('sample_key');

        $this->assertEquals('sample_key', $this->iterableResultDecorator->key());
    }

    public function testValid()
    {
        $this->iterableResult->expects($this->once())
            ->method('valid')
            ->willReturn(true);

        $this->assertEquals(true, $this->iterableResultDecorator->valid());
    }
}
