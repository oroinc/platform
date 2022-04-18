<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\ClearableCache;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\EventListener\ClearCacheOnConfigUpdateListener;

class ClearCacheOnConfigUpdateListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SAMPLE_CONFIG_PARAMETER = 'sample.config.parameter';

    private ClearCacheOnConfigUpdateListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ClearCacheOnConfigUpdateListener(self::SAMPLE_CONFIG_PARAMETER);
    }

    public function testOnUpdateAfterWhenNoCachesToClear(): void
    {
        $this->expectNotToPerformAssertions();

        $event = new ConfigUpdateEvent([self::SAMPLE_CONFIG_PARAMETER => 'sample_value']);

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterWhenNotChanged(): void
    {
        $clearableCache = $this->createMock(ClearableCache::class);
        $clearableCache
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->addCacheToClear($clearableCache);
        $this->listener->onUpdateAfter(new ConfigUpdateEvent([]));
    }

    public function testOnUpdateAfter(): void
    {
        $clearableCache = $this->createMock(ClearableCache::class);
        $clearableCache
            ->expects(self::once())
            ->method('deleteAll');

        $this->listener->addCacheToClear($clearableCache);
        $this->listener->onUpdateAfter(new ConfigUpdateEvent([self::SAMPLE_CONFIG_PARAMETER => 'sample_value']));
    }
}
