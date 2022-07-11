<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\CacheableDynamicTranslationLoader;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationLoaderInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationError;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationErrorCollection;
use Oro\Bundle\TranslationBundle\Translation\TranslationsSanitizer;

class CacheableDynamicTranslationLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DynamicTranslationLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $loader;

    /** @var DynamicTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var TranslationsSanitizer|\PHPUnit\Framework\MockObject\MockObject */
    private $sanitizer;

    /** @var TranslationMessageSanitizationErrorCollection */
    private $sanitizationErrorCollection;

    /** @var CacheableDynamicTranslationLoader */
    private $cacheableLoader;

    protected function setUp(): void
    {
        $this->loader = $this->createMock(DynamicTranslationLoaderInterface::class);
        $this->cache = $this->createMock(DynamicTranslationCache::class);
        $this->sanitizer = $this->createMock(TranslationsSanitizer::class);
        $this->sanitizationErrorCollection = new TranslationMessageSanitizationErrorCollection();

        $this->cacheableLoader = new CacheableDynamicTranslationLoader(
            $this->loader,
            $this->cache,
            $this->sanitizer,
            $this->sanitizationErrorCollection
        );
    }

    /**
     * @dataProvider loadTranslationsDataProvider
     */
    public function testLoadTranslations(bool $includeSystem): void
    {
        $locales = ['en_US', 'en'];
        $translations = [
            'en'    => [
                'messages' => [
                    'foo' => 'foo (EN)',
                    'bar' => 'bar (EN)'
                ]
            ],
            'en_US' => [
                'messages' => [
                    'foo' => 'foo (EN_US)'
                ]
            ]
        ];
        $sanitizationError = new TranslationMessageSanitizationError(
            'en',
            'messages',
            'foo',
            $translations['en']['messages']['foo'],
            'sanitized foo (EN)'
        );

        $this->cache->expects(self::once())
            ->method('get')
            ->with($locales, self::isType('callable'))
            ->willReturnCallback(function (array $locales, callable $callback): array {
                return $callback($locales);
            });
        $this->loader->expects(self::once())
            ->method('loadTranslations')
            ->with($locales, self::identicalTo($includeSystem))
            ->willReturn($translations);
        $this->sanitizer->expects(self::exactly(2))
            ->method('sanitizeTranslations')
            ->withConsecutive(
                [$translations['en'], 'en'],
                [$translations['en_US'], 'en_US']
            )
            ->willReturnOnConsecutiveCalls(
                [$sanitizationError],
                []
            );

        self::assertEquals(
            [
                'en'    => [
                    'messages' => [
                        'foo' => 'sanitized foo (EN)',
                        'bar' => 'bar (EN)'
                    ]
                ],
                'en_US' => [
                    'messages' => [
                        'foo' => 'foo (EN_US)'
                    ]
                ]
            ],
            $this->cacheableLoader->loadTranslations($locales, $includeSystem)
        );
        self::assertEquals(
            [$sanitizationError],
            $this->sanitizationErrorCollection->all()
        );
    }

    public function loadTranslationsDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }
}
