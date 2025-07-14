<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\EventListener\ClearCacheOnConfigUpdateListener;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class ClearCacheOnConfigUpdateListenerTest extends TestCase
{
    private const SAMPLE_CONFIG_PARAMETER = 'sample.config.parameter';

    private ClearCacheOnConfigUpdateListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new ClearCacheOnConfigUpdateListener(self::SAMPLE_CONFIG_PARAMETER);
    }

    public function testOnUpdateAfterWhenNoCachesToClear(): void
    {
        $this->expectNotToPerformAssertions();

        $event = new ConfigUpdateEvent([self::SAMPLE_CONFIG_PARAMETER => 'sample_value'], 'global', 0);

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterWhenNotChanged(): void
    {
        $cacheToClear = $this->createMock(CacheItemPoolInterface::class);
        $cacheToClear->expects(self::never())
            ->method(self::anything());

        $this->listener->addCacheToClear($cacheToClear);
        $this->listener->onUpdateAfter(new ConfigUpdateEvent([], 'global', 0));
    }

    public function testOnUpdateAfter(): void
    {
        $cacheToClear = $this->createMock(CacheItemPoolInterface::class);
        $cacheToClear->expects(self::once())
            ->method('clear');

        $this->listener->addCacheToClear($cacheToClear);
        $this->listener->onUpdateAfter(
            new ConfigUpdateEvent([self::SAMPLE_CONFIG_PARAMETER => 'sample_value'], 'global', 0)
        );
    }
}
