<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\TranslationBundle\Translation\CachingTranslationLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class CachingTranslationLoaderTest extends TestCase
{
    private LoaderInterface&MockObject $innerLoader;
    private MemoryCache&MockObject $cache;
    private CachingTranslationLoader $cachingLoader;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->cache = $this->createMock(MemoryCache::class);

        $this->cachingLoader = new CachingTranslationLoader($this->innerLoader, $this->cache);
    }

    public function testLoadForStringResourceType(): void
    {
        $locale = 'fr';
        $domain = 'test';
        $resource = 'test_resource';

        $catalogue = new MessageCatalogue($locale);

        $this->cache->expects(self::exactly(2))
            ->method('get')
            ->with(sprintf('%s_%s_%s', $locale, $domain, $resource))
            ->willReturnOnConsecutiveCalls(null, $catalogue);
        $this->innerLoader->expects(self::once())
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

    public function testLoadForSupportedObjectResourceType(): void
    {
        $locale = 'fr';
        $domain = 'test';
        $resourceFile = 'test_file.yml';
        $resource = $this->createMock(ResourceInterface::class);
        $resource->expects($this->atLeastOnce())
            ->method('__toString')
            ->willReturn($resourceFile);

        $catalogue = new MessageCatalogue($locale);

        $this->cache->expects(self::exactly(2))
            ->method('get')
            ->with(sprintf('%s_%s_%s', $locale, $domain, $resourceFile))
            ->willReturnOnConsecutiveCalls(null, $catalogue);
        $this->innerLoader->expects($this->once())
            ->method('load')
            ->with($resourceFile, $locale, $domain)
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

    public function testLoadForUnsupportedResourceType(): void
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

    public function testLoadForEmptyStringResourceType(): void
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
