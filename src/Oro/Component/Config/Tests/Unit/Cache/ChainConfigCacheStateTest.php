<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\Cache\ChainConfigCacheState;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainConfigCacheStateTest extends TestCase
{
    private ConfigCacheStateInterface&MockObject $configCacheState1;
    private ConfigCacheStateInterface&MockObject $configCacheState2;
    private ChainConfigCacheState $chainConfigCacheState;

    #[\Override]
    protected function setUp(): void
    {
        $this->configCacheState1 = $this->createMock(ConfigCacheStateInterface::class);
        $this->configCacheState2 = $this->createMock(ConfigCacheStateInterface::class);

        $this->chainConfigCacheState = new ChainConfigCacheState([
            $this->configCacheState1,
            $this->configCacheState2
        ]);
    }

    public function testAddConfigCacheState(): void
    {
        $timestamp = 123;

        $configCacheState3 = $this->createMock(ConfigCacheStateInterface::class);

        $this->configCacheState1->expects(self::exactly(2))
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(true);
        $this->configCacheState2->expects(self::exactly(2))
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(true);
        $configCacheState3->expects(self::once())
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(false);

        self::assertTrue($this->chainConfigCacheState->isCacheFresh($timestamp));

        $this->chainConfigCacheState->addConfigCacheState($configCacheState3);
        self::assertFalse($this->chainConfigCacheState->isCacheFresh($timestamp));
    }

    public function testIsCacheFreshForNullTimestamp(): void
    {
        $this->configCacheState1->expects(self::once())
            ->method('isCacheFresh')
            ->with(null)
            ->willReturn(true);
        $this->configCacheState2->expects(self::once())
            ->method('isCacheFresh')
            ->with(null)
            ->willReturn(true);

        self::assertTrue($this->chainConfigCacheState->isCacheFresh(null));
    }

    public function testIsCacheFreshWhenAllChildrenAreFresh(): void
    {
        $timestamp = 123;

        $this->configCacheState1->expects(self::once())
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(true);
        $this->configCacheState2->expects(self::once())
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(true);

        self::assertTrue($this->chainConfigCacheState->isCacheFresh($timestamp));
    }

    public function testIsCacheFreshWhenFirstChildIsDirty(): void
    {
        $timestamp = 123;

        $this->configCacheState1->expects(self::once())
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(false);
        $this->configCacheState2->expects(self::never())
            ->method('isCacheFresh');

        self::assertFalse($this->chainConfigCacheState->isCacheFresh($timestamp));
    }

    public function testIsCacheFreshWhenSecondChildIsDirty(): void
    {
        $timestamp = 123;

        $this->configCacheState1->expects(self::once())
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(true);
        $this->configCacheState2->expects(self::once())
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(false);

        self::assertFalse($this->chainConfigCacheState->isCacheFresh($timestamp));
    }

    public function testGetCacheTimestampWhenAllChildrenAreNotBuiltYet(): void
    {
        $this->configCacheState1->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn(null);
        $this->configCacheState2->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn(null);

        self::assertNull($this->chainConfigCacheState->getCacheTimestamp());
    }

    public function testIsCacheFreshWhenFirstChildIsIsNotBuiltYet(): void
    {
        $timestamp1 = null;
        $timestamp2 = 123;

        $this->configCacheState1->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp1);
        $this->configCacheState2->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp2);

        self::assertSame($timestamp2, $this->chainConfigCacheState->getCacheTimestamp());
    }

    public function testIsCacheFreshWhenSecondChildIsNotBuiltYet(): void
    {
        $timestamp1 = 123;
        $timestamp2 = null;

        $this->configCacheState1->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp1);
        $this->configCacheState2->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp2);

        self::assertSame($timestamp1, $this->chainConfigCacheState->getCacheTimestamp());
    }

    public function testIsCacheFreshWhenFirstChildIsBuiltBeforeSecondChild(): void
    {
        $timestamp1 = 123;
        $timestamp2 = 124;

        $this->configCacheState1->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp1);
        $this->configCacheState2->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp2);

        self::assertSame($timestamp2, $this->chainConfigCacheState->getCacheTimestamp());
    }

    public function testIsCacheFreshWhenFirstChildIsBuiltAfterSecondChild(): void
    {
        $timestamp1 = 124;
        $timestamp2 = 123;

        $this->configCacheState1->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp1);
        $this->configCacheState2->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp2);

        self::assertSame($timestamp1, $this->chainConfigCacheState->getCacheTimestamp());
    }
}
