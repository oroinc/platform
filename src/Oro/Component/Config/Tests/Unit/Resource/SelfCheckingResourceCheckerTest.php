<?php

namespace Oro\Component\Config\Tests\Unit\Resource;

use Oro\Component\Config\Resource\SelfCheckingResourceChecker;
use Oro\Component\Config\Tests\Unit\Fixtures\ResourceStub;
use Symfony\Component\Config\Resource\SelfCheckingResourceChecker as SymfonySelfCheckingResourceChecker;

class SelfCheckingResourceCheckerTest extends \PHPUnit\Framework\TestCase
{
    private SymfonySelfCheckingResourceChecker|\PHPUnit\Framework\MockObject\MockObject $innerResourceChecker;

    protected function setUp(): void
    {
        $this->innerResourceChecker = $this->createMock(SymfonySelfCheckingResourceChecker::class);
    }

    public function testSupportsCallsInnerResourceChecker(): void
    {
        $metadata = new ResourceStub();

        $this->innerResourceChecker->expects(self::once())
            ->method('supports')
            ->with($metadata)
            ->willReturn(false);

        self::assertFalse((new SelfCheckingResourceChecker(true, $this->innerResourceChecker))->supports($metadata));
    }

    public function testSupportsCallsCreatedResourceChecker(): void
    {
        $metadata = new ResourceStub();

        self::assertTrue((new SelfCheckingResourceChecker(true))->supports($metadata));
    }

    public function testIsFreshCallsInnerResourceCheckerWhenNotDebug(): void
    {
        $resource = new ResourceStub();
        $timestamp = time();

        $this->innerResourceChecker->expects(self::once())
            ->method('isFresh')
            ->with($resource, $timestamp)
            ->willReturn(false);

        self::assertFalse(
            (new SelfCheckingResourceChecker(false, $this->innerResourceChecker))->isFresh($resource, $timestamp)
        );
    }

    public function testIsFreshCallsCreatedResourceCheckerWhenNotDebug(): void
    {
        $resource = new ResourceStub();

        self::assertTrue((new SelfCheckingResourceChecker(false))->isFresh($resource, time()));
    }

    public function testIsFreshDoesNotCallInnerResourceCheckerWhenDebug(): void
    {
        $resource = new ResourceStub();
        $timestamp = time();

        $this->innerResourceChecker->expects(self::never())
            ->method('isFresh');

        self::assertTrue(
            (new SelfCheckingResourceChecker(true, $this->innerResourceChecker))->isFresh($resource, $timestamp)
        );
    }

    public function testIsFreshDoesNotCallCreatedResourceCheckerWhenDebug(): void
    {
        $resource = new ResourceStub();
        $timestamp = time();

        $this->innerResourceChecker->expects(self::never())
            ->method('isFresh');

        self::assertTrue(
            (new SelfCheckingResourceChecker(true))->isFresh($resource, $timestamp)
        );
    }
}
