<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\CachingTranslationLoader;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachingTranslationLoaderTest extends \PHPUnit\Framework\TestCase
{

    /** @var LoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerLoader;

    /** @var CachingTranslationLoader */
    private $cachingLoader;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp(): void
    {
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cachingLoader = new CachingTranslationLoader(
            $this->innerLoader,
            $this->cache
        );
    }

    public function testLoadForStringResourceType()
    {
        $locale = 'fr';
        $domain = 'test';
        $resource = 'test_resource';

        $catalogue = new MessageCatalogue($locale);

        $this->cache->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        $this->innerLoader->expects(self::exactly(2))
            ->method('load')
            ->with($resource, $locale, $domain)
            ->willReturn($catalogue);

        $this->assertSame(
            $catalogue,
            $this->cachingLoader->load($resource, $locale, $domain)
        );
        // test that the result was cached
        $this->assertSame(
            $catalogue,
            $this->cachingLoader->load($resource, $locale, $domain)
        );
    }

    public function testLoadForSupportedObjectResourceType()
    {
        $locale = 'fr';
        $domain = 'test';
        $metadataCache = $this->createMock(DynamicTranslationMetadataCache::class);
        $resource = new OrmTranslationResource($locale, $metadataCache);

        $catalogue = new MessageCatalogue($locale);

        $saveCallback = function ($cacheKey, $callback) {
            $item = $this->createMock(ItemInterface::class);
            return $callback($item);
        };
        $this->cache->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(new ReturnCallback($saveCallback), $catalogue);
        $this->innerLoader->expects($this->once())
            ->method('load')
            ->with((string)$resource, $locale, $domain)
            ->willReturn($catalogue);

        $this->assertSame(
            $catalogue,
            $this->cachingLoader->load($resource, $locale, $domain)
        );
        // test that the result was cached
        $this->assertSame(
            $catalogue,
            $this->cachingLoader->load($resource, $locale, $domain)
        );
    }

    public function testLoadForUnsupportedResourceType()
    {
        $locale = 'fr';
        $domain = 'test';
        $resource = new \stdClass();

        $catalogue = new MessageCatalogue($locale);

        $this->innerLoader->expects($this->exactly(2))
            ->method('load')
            ->with($this->identicalTo($resource), $locale, $domain)
            ->willReturn($catalogue);

        $this->assertSame(
            $catalogue,
            $this->cachingLoader->load($resource, $locale, $domain)
        );
        // test that the result was not cached
        $this->assertSame(
            $catalogue,
            $this->cachingLoader->load($resource, $locale, $domain)
        );
    }

    public function testLoadForEmptyStringResourceType()
    {
        $locale = 'fr';
        $domain = 'test';
        $resource = '';

        $catalogue = new MessageCatalogue($locale);

        $this->innerLoader->expects($this->exactly(2))
            ->method('load')
            ->with($resource, $locale, $domain)
            ->willReturn($catalogue);

        $this->assertSame(
            $catalogue,
            $this->cachingLoader->load($resource, $locale, $domain)
        );
        // test that the result was not cached
        $this->assertSame(
            $catalogue,
            $this->cachingLoader->load($resource, $locale, $domain)
        );
    }
}
