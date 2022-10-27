<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

abstract class EnumValueListenerTestCase extends \PHPUnit\Framework\TestCase
{
    protected const ENUM_VALUE_CLASS = TestEnumValue::class;

    /** @var EnumTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(EnumTranslationCache::class);
    }

    protected function assertClearCacheCalled()
    {
        $this->cache->expects($this->once())
            ->method('invalidate')
            ->with(self::ENUM_VALUE_CLASS);
    }

    protected function assertClearCacheNotCalled()
    {
        $this->cache->expects($this->never())
            ->method('invalidate');
    }
}
