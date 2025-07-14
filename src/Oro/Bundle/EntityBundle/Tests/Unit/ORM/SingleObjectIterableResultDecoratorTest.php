<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Oro\Bundle\EntityBundle\ORM\SingleObjectIterableResultDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SingleObjectIterableResultDecoratorTest extends TestCase
{
    private IterableResult&MockObject $iterableResult;
    private SingleObjectIterableResultDecorator $iterableResultDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->iterableResult = $this->createMock(IterableResult::class);

        $this->iterableResultDecorator = new SingleObjectIterableResultDecorator($this->iterableResult);
    }

    public function testRewind(): void
    {
        $this->iterableResult->expects($this->once())
            ->method('rewind');

        $this->iterableResultDecorator->rewind();
    }

    public function testNextWhenArrayIsReturned(): void
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

    public function testNextWhenNotArrayIsReturned(): void
    {
        $this->iterableResult->expects($this->once())
            ->method('next');
        $this->iterableResult->expects($this->once())
            ->method('current')
            ->willReturn(false);
        $this->iterableResultDecorator->next();
        $this->assertEquals(false, $this->iterableResultDecorator->current());
    }

    public function testCurrent(): void
    {
        $sampleObject = new \stdClass();

        $this->iterableResult->expects($this->once())
            ->method('current')
            ->willReturn([$sampleObject]);

        $this->assertEquals($sampleObject, $this->iterableResultDecorator->current());
    }

    public function testKey(): void
    {
        $this->iterableResult->expects($this->once())
            ->method('key')
            ->willReturn('sample_key');

        $this->assertEquals('sample_key', $this->iterableResultDecorator->key());
    }

    public function testValid(): void
    {
        $this->iterableResult->expects($this->once())
            ->method('valid')
            ->willReturn(true);

        $this->assertEquals(true, $this->iterableResultDecorator->valid());
    }
}
