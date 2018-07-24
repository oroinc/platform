<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

abstract class EnumValueListenerTestCase extends \PHPUnit\Framework\TestCase
{
    const ENUM_VALUE_CLASS = StubEnumValue::class;

    /** @var EnumTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    public function setUp()
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
