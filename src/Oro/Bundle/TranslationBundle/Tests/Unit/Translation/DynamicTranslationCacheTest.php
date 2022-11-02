<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Event\InvalidateDynamicTranslationCacheEvent;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DynamicTranslationCacheTest extends \PHPUnit\Framework\TestCase
{
    private const NOT_CACHED_TRANSLATIONS = [
        'en' => ['messages' => ['foo' => 'foo (EN) (not cached)']]
    ];

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheImpl;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var callable */
    private $callback;

    /** @var DynamicTranslationCache */
    private $cache;

    protected function setUp(): void
    {
        $this->cacheImpl = $this->createMock(CacheItemPoolInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->callback = function (array $notCachedLocales): array {
            $result = [];
            foreach ($notCachedLocales as $loc) {
                if (isset(self::NOT_CACHED_TRANSLATIONS[$loc])) {
                    $result[$loc] = self::NOT_CACHED_TRANSLATIONS[$loc];
                }
            }

            return $result;
        };

        $this->cache = new DynamicTranslationCache($this->cacheImpl, $this->eventDispatcher);
    }

    private function getCacheItem(bool $isHit = false, array $value = null): CacheItemInterface
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects(self::any())
            ->method('isHit')
            ->willReturn($isHit);
        $item->expects(self::any())
            ->method('get')
            ->willReturn($value);

        return $item;
    }

    public function testGetWhenNoCachedTranslations(): void
    {
        $this->cacheImpl->expects(self::once())
            ->method('getItems')
            ->with(['dynamic_translations_en_US', 'dynamic_translations_en'])
            ->willReturnCallback(function (array $keys) {
                $result = [];
                foreach ($keys as $key) {
                    $result[$key] = $this->getCacheItem();
                }

                return $result;
            });

        $this->cacheImpl->expects(self::exactly(2))
            ->method('saveDeferred')
            ->withConsecutive(
                [$this->getCacheItem(false, [])],
                [$this->getCacheItem(false, self::NOT_CACHED_TRANSLATIONS['en'])]
            );
        $this->cacheImpl->expects(self::once())
            ->method('commit');

        self::assertSame(
            [
                'en_US' => [],
                'en'    => self::NOT_CACHED_TRANSLATIONS['en']
            ],
            $this->cache->get(['en_US', 'en'], $this->callback)
        );
    }

    public function testGetWhenAllLocalesHaveCachedTranslations(): void
    {
        $cachedTranslations = [
            'dynamic_translations_en_US' => ['messages' => ['foo' => 'foo (EN_US) (cached)']],
            'dynamic_translations_en'    => ['messages' => ['foo' => 'foo (EN) (cached)']]
        ];

        $this->cacheImpl->expects(self::once())
            ->method('getItems')
            ->with(['dynamic_translations_en_US', 'dynamic_translations_en'])
            ->willReturnCallback(function (array $keys) use ($cachedTranslations) {
                $result = [];
                foreach ($keys as $key) {
                    $result[$key] = $this->getCacheItem(true, $cachedTranslations[$key]);
                }

                return $result;
            });

        $this->cacheImpl->expects(self::never())
            ->method('saveDeferred');
        $this->cacheImpl->expects(self::never())
            ->method('commit');

        self::assertSame(
            [
                'en_US' => $cachedTranslations['dynamic_translations_en_US'],
                'en'    => $cachedTranslations['dynamic_translations_en']
            ],
            $this->cache->get(['en_US', 'en'], $this->callback)
        );
    }

    public function testGetWhenSomeLocalesHaveCachedTranslations(): void
    {
        $cachedTranslations = [
            'dynamic_translations_en_US' => ['messages' => ['foo' => 'foo (EN_US) (cached)']]
        ];

        $this->cacheImpl->expects(self::once())
            ->method('getItems')
            ->with(['dynamic_translations_en_US', 'dynamic_translations_en'])
            ->willReturnCallback(function (array $keys) use ($cachedTranslations) {
                $result = [];
                foreach ($keys as $key) {
                    $result[$key] = isset($cachedTranslations[$key])
                        ? $this->getCacheItem(true, $cachedTranslations[$key])
                        : $this->getCacheItem();
                }

                return $result;
            });

        $this->cacheImpl->expects(self::once())
            ->method('saveDeferred')
            ->with($this->getCacheItem(false, self::NOT_CACHED_TRANSLATIONS['en']));
        $this->cacheImpl->expects(self::once())
            ->method('commit');

        self::assertSame(
            [
                'en_US' => $cachedTranslations['dynamic_translations_en_US'],
                'en'    => self::NOT_CACHED_TRANSLATIONS['en']
            ],
            $this->cache->get(['en_US', 'en'], $this->callback)
        );
    }

    public function testDelete(): void
    {
        $locales = ['en_US', 'en'];

        $this->cacheImpl->expects(self::once())
            ->method('deleteItems')
            ->with(['dynamic_translations_en_US', 'dynamic_translations_en']);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new InvalidateDynamicTranslationCacheEvent($locales),
                InvalidateDynamicTranslationCacheEvent::NAME
            );

        $this->cache->delete($locales);
    }

    public function testDeleteWithEmptyLocales(): void
    {
        $this->cacheImpl->expects(self::never())
            ->method('deleteItems');
        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->cache->delete([]);
    }
}
