<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationLoaderInterface;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DynamicTranslationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DynamicTranslationLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $loader;

    /** @var DynamicTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var DynamicTranslationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->loader = $this->createMock(DynamicTranslationLoaderInterface::class);
        $this->cache = $this->createMock(DynamicTranslationCache::class);

        $this->provider = new DynamicTranslationProvider($this->loader, $this->cache);
        $this->provider->setFallbackLocales(['en_US', 'en']);
    }

    private function expectsLoadTranslations(array $locales, array $data): void
    {
        $translations = [];
        foreach ($data as $item) {
            $translations[$item['locale']][$item['domain']][$item['key']] = $item['value'];
        }

        $this->loader->expects(self::once())
            ->method('loadTranslations')
            ->with($locales, self::isFalse())
            ->willReturn($translations);
    }

    /**
     * @dataProvider localeListBuildingDataProvider
     */
    public function testLocaleListBuilding(string $locale, array $expectedLocaleList): void
    {
        $this->expectsLoadTranslations($expectedLocaleList, []);

        $this->provider->hasTranslation('foo', 'messages', $locale);

        // test memory cache
        $this->provider->hasTranslation('foo', 'messages', $locale);
    }

    public function localeListBuildingDataProvider(): array
    {
        return [
            ['en', ['en_US', 'en']],
            ['en_US', ['en_US', 'en']],
            ['fr', ['en_US', 'en', 'fr']],
        ];
    }

    public function testGetTranslation(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
            ['locale' => 'en_US', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN_US) (scope=installed)'],
            ['locale' => 'en_US', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN_US)'],
        ]);

        self::assertEquals(
            'foo (EN)',
            $this->provider->getTranslation('foo', 'messages', 'en')
        );
        self::assertEquals(
            'foo (EN_US)',
            $this->provider->getTranslation('foo', 'messages', 'en_US')
        );

        // test memory cache
        self::assertEquals(
            'foo (EN)',
            $this->provider->getTranslation('foo', 'messages', 'en')
        );
        self::assertEquals(
            'foo (EN_US)',
            $this->provider->getTranslation('foo', 'messages', 'en_US')
        );
    }

    public function testHasTranslation(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
            ['locale' => 'en_US', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN_US) (scope=installed)'],
            ['locale' => 'en_US', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN_US)'],
        ]);

        self::assertTrue($this->provider->hasTranslation('foo', 'messages', 'en'));
        self::assertTrue($this->provider->hasTranslation('foo', 'messages', 'en_US'));

        // test memory cache
        self::assertTrue($this->provider->hasTranslation('foo', 'messages', 'en'));
        self::assertTrue($this->provider->hasTranslation('foo', 'messages', 'en_US'));
    }

    public function testGetTranslationForNotExistingTranslationKey(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The translation "messages" -> "not_existing" (en) does not exist.');

        $this->provider->getTranslation('not_existing', 'messages', 'en');
    }

    public function testGetTranslationForNotExistingTranslationDomain(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The translation "not_existing" -> "foo" (en) does not exist.');

        $this->provider->getTranslation('foo', 'not_existing', 'en');
    }

    public function testGetTranslationForNotExistingTranslationLocale(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en', 'not_existing'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The translation "messages" -> "foo" (not_existing) does not exist.');

        $this->provider->getTranslation('foo', 'messages', 'not_existing');
    }

    public function testHasTranslationForNotExistingTranslationKey(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
        ]);

        self::assertFalse($this->provider->hasTranslation('not_existing', 'messages', 'en'));

        // test memory cache
        self::assertFalse($this->provider->hasTranslation('not_existing', 'messages', 'en'));
    }

    public function testHasTranslationForNotExistingTranslationDomain(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
        ]);

        self::assertFalse($this->provider->hasTranslation('foo', 'not_existing', 'en'));

        // test memory cache
        self::assertFalse($this->provider->hasTranslation('foo', 'not_existing', 'en'));
    }

    public function testHasTranslationForNotExistingTranslationLocale(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en', 'not_existing'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
        ]);

        self::assertFalse($this->provider->hasTranslation('foo', 'messages', 'not_existing'));

        // test memory cache
        self::assertFalse($this->provider->hasTranslation('foo', 'messages', 'not_existing'));
    }

    public function testGetTranslations(): void
    {
        $this->expectsLoadTranslations(['en_US', 'en'], [
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN)'],
            ['locale' => 'en', 'domain' => 'messages', 'key' => 'bar', 'value' => 'bar (EN)'],
            ['locale' => 'en_US', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN_US) (scope=installed)'],
            ['locale' => 'en_US', 'domain' => 'messages', 'key' => 'foo', 'value' => 'foo (EN_US)'],
            ['locale' => 'en_US', 'domain' => 'messages', 'key' => 'baz', 'value' => 'baz (EN_US)'],
        ]);

        self::assertEquals(
            ['foo' => 'foo (EN)', 'bar' => 'bar (EN)'],
            $this->provider->getTranslations('messages', 'en')
        );
        self::assertEquals(
            ['foo' => 'foo (EN_US)', 'baz' => 'baz (EN_US)'],
            $this->provider->getTranslations('messages', 'en_US')
        );

        // test memory cache
        self::assertEquals(
            ['foo' => 'foo (EN)', 'bar' => 'bar (EN)'],
            $this->provider->getTranslations('messages', 'en')
        );
        self::assertEquals(
            ['foo' => 'foo (EN_US)', 'baz' => 'baz (EN_US)'],
            $this->provider->getTranslations('messages', 'en_US')
        );
    }

    public function testWarmUp(): void
    {
        $locale = 'en_US';

        $this->cache->expects(self::exactly(2))
            ->method('delete')
            ->with([$locale]);
        $this->loader->expects(self::exactly(2))
            ->method('loadTranslations')
            ->with([$locale], self::isFalse())
            ->willReturn([]);

        $this->provider->warmUp([$locale]);

        // test that memory cache is cleared
        $this->provider->warmUp([$locale]);
    }
}
