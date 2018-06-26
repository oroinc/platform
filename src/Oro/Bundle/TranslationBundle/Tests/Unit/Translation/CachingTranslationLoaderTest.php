<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\Common\Cache\ArrayCache;
use Oro\Bundle\TranslationBundle\Translation\CachingTranslationLoader;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;
use Symfony\Component\Translation\MessageCatalogue;

class CachingTranslationLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $innerLoader;

    /** @var CachingTranslationLoader */
    protected $cachingLoader;

    protected function setUp()
    {
        $this->innerLoader = $this->createMock('Symfony\Component\Translation\Loader\LoaderInterface');

        $this->cachingLoader = new CachingTranslationLoader(
            $this->innerLoader,
            new ArrayCache()
        );
    }

    public function testLoadForStringResourceType()
    {
        $locale   = 'fr';
        $domain   = 'test';
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
        $locale        = 'fr';
        $domain        = 'test';
        $metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
        $resource      = new OrmTranslationResource($locale, $metadataCache);

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
        $locale   = 'fr';
        $domain   = 'test';
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
        $locale   = 'fr';
        $domain   = 'test';
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
