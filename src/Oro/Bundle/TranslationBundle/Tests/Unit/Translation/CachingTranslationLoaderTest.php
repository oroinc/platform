<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\CachingTranslationLoader;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;
use Oro\Component\Testing\Unit\Cache\CacheTrait;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class CachingTranslationLoaderTest extends \PHPUnit\Framework\TestCase
{
    use CacheTrait;

    /** @var LoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerLoader;

    /** @var CachingTranslationLoader */
    private $cachingLoader;

    protected function setUp(): void
    {
        $this->innerLoader = $this->createMock(LoaderInterface::class);

        $this->cachingLoader = new CachingTranslationLoader(
            $this->innerLoader,
            $this->getArrayCache()
        );
    }

    public function testLoadForStringResourceType()
    {
        $locale = 'fr';
        $domain = 'test';
        $resource = 'test_resource';

        $catalogue = new MessageCatalogue($locale);

        $this->innerLoader->expects($this->once())
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
