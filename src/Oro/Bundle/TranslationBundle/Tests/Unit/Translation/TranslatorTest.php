<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationProvider;
use Oro\Bundle\TranslationBundle\Translation\MessageCatalogueSanitizer;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationErrorCollection;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TranslatorTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const MESSAGES = [
        'fr'    => [
            'jsmessages' => [
                'foo' => 'foo (FR)',
            ],
            'messages'   => [
                'foo' => 'foo messages (FR)',
            ],
        ],
        'en'    => [
            'jsmessages' => [
                'foo' => 'foo (EN)',
                'bar' => 'bar (EN)',
                'baz' => 'baz (EN)',
            ],
            'messages'   => [
                'foo' => 'foo messages (EN)',
            ],
            'validators' => [
                'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
            ],
        ],
        'es'    => [
            'jsmessages' => [
                'foobar' => 'foobar (ES)',
            ],
            'messages'   => [
                'foo' => 'foo messages (ES)',
            ],
        ],
        'pt-PT' => [
            'jsmessages' => [
                'foobarfoo' => 'foobarfoo (PT-PT)',
                'foo'       => 'foo (PT-PT)',
            ],
        ],
        'pt_BR' => [
            'validators' => [
                'other choice' =>
                    '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
            ],
        ],
    ];

    /** @var TranslationMessageSanitizationErrorCollection */
    private $sanitizationErrorCollection;

    protected function setUp(): void
    {
        $this->sanitizationErrorCollection = new TranslationMessageSanitizationErrorCollection();
    }

    private function getTranslator(string $locale, TranslationStrategyProvider $strategyProvider): Translator
    {
        $cacheDir = $this->getTempDir('translator');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::any())
            ->method('get')
            ->with('loader')
            ->willReturn($this->getLoader(self::MESSAGES));

        $translator = new Translator(
            $container,
            new MessageFormatter(),
            $locale,
            ['loader' => ['loader']],
            ['resource_files' => [], 'cache_dir' => $cacheDir]
        );


        $translator->setStrategyProvider($strategyProvider);
        $translator->setResourceCache(new MemoryCache());
        $translator->setMessageCatalogueSanitizer($this->createMock(MessageCatalogueSanitizer::class));
        $translator->setSanitizationErrorCollection($this->sanitizationErrorCollection);
        $translator->setDynamicTranslationProvider($this->getDynamicTranslationProvider([]));

        $translator->addResource('loader', 'foo.fr.loader', 'fr');
        $translator->addResource('loader', 'foo.en.loader', 'en');
        $translator->addResource('loader', 'foo.es.loader', 'es');
        $translator->addResource('loader', 'foo.pt-PT.loader', 'pt-PT'); // European Portuguese
        $translator->addResource('loader', 'foo.pt_BR.loader', 'pt_BR'); // Brazilian Portuguese

        return $translator;
    }

    private function getLoader(array $messages): LoaderInterface
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects(self::any())
            ->method('load')
            ->willReturnCallback(function ($resource, $locale) use ($messages) {
                return $this->getCatalogue($locale, $messages[$locale]);
            });

        return $loader;
    }

    private function getCatalogue(string $locale, array $dictionary): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);
        foreach ($dictionary as $domain => $messages) {
            foreach ($messages as $key => $translation) {
                $catalogue->set($key, $translation, $domain);
            }
        }

        return $catalogue;
    }

    private function getStrategyProvider(string $locale, array $fallbackLocales = []): TranslationStrategyProvider
    {
        $strategy = $this->getStrategy('test_strategy');

        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $strategyProvider->expects(self::any())
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects(self::any())
            ->method('getStrategies')
            ->willReturn([$strategy]);
        $strategyProvider->expects(self::any())
            ->method('getFallbackLocales')
            ->willReturnCallback(function ($strategy, $loc) use ($locale, $fallbackLocales) {
                if ($loc === $locale) {
                    $i = array_search($loc, $fallbackLocales, true);
                    if (false !== $i) {
                        unset($fallbackLocales[$i]);
                    }

                    return $fallbackLocales;
                }

                return [];
            });
        $strategyProvider->expects(self::any())
            ->method('getAllFallbackLocales')
            ->with(self::identicalTo($strategy))
            ->willReturn($fallbackLocales);

        return $strategyProvider;
    }

    private function getStrategy(string $strategyName): TranslationStrategyInterface
    {
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategy->expects(self::any())
            ->method('getName')
            ->willReturn($strategyName);

        return $strategy;
    }

    private function getDynamicTranslationProvider(array $translations): DynamicTranslationProvider
    {
        return new DynamicTranslationProvider(
            new DynamicTranslationLoaderStub($translations),
            $this->createMock(DynamicTranslationCache::class)
        );
    }

    /**
     * @dataProvider getTranslationsDataProvider
     */
    public function testGetTranslations(?string $locale, array $expected): void
    {
        $locales = array_keys(self::MESSAGES);
        $translatorLocale = $locale ?? $locales[0];
        $fallbackLocales = array_slice($locales, array_search($translatorLocale, $locales, true));

        $translator = $this->getTranslator(
            $translatorLocale,
            $this->getStrategyProvider($translatorLocale, $fallbackLocales)
        );
        $translator->rebuildCache();

        $result = $translator->getTranslations(['jsmessages', 'validators'], $locale);

        self::assertEquals($expected, $result);
    }

    public function getTranslationsDataProvider(): array
    {
        return [
            [
                null,
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                        'choice'       => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar'    => 'foobar (ES)',
                        'foo'       => 'foo (FR)',
                        'bar'       => 'bar (EN)',
                        'baz'       => 'baz (EN)',
                    ]
                ]
            ],
            [
                'fr',
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                        'choice'       => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar'    => 'foobar (ES)',
                        'foo'       => 'foo (FR)',
                        'bar'       => 'bar (EN)',
                        'baz'       => 'baz (EN)',
                    ]
                ]
            ],
            [
                'en',
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                        'choice'       => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar'    => 'foobar (ES)',
                        'foo'       => 'foo (EN)',
                        'bar'       => 'bar (EN)',
                        'baz'       => 'baz (EN)',
                    ]
                ]
            ],
            [
                'es',
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foo'       => 'foo (PT-PT)',
                        'foobar'    => 'foobar (ES)',
                    ]
                ]
            ],
            [
                'pt-PT',
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foo'       => 'foo (PT-PT)',
                    ]
                ]
            ],
            [
                'pt_BR',
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                    ],
                    'jsmessages' => []
                ]
            ],
        ];
    }

    /**
     * @dataProvider getTranslationsWithDynamicTranslationsDataProvider
     */
    public function testGetTranslationsWithDynamicTranslations(?string $locale, array $expected): void
    {
        $locales = array_keys(self::MESSAGES);
        $translatorLocale = $locale ?? $locales[0];
        $fallbackLocales = array_slice($locales, array_search($translatorLocale, $locales, true));

        $translator = $this->getTranslator(
            $translatorLocale,
            $this->getStrategyProvider($translatorLocale, $fallbackLocales)
        );
        $translator->setDynamicTranslationProvider($this->getDynamicTranslationProvider([
            'en' => [
                'jsmessages' => [
                    'foo'     => 'foo (EN) (dynamic)',
                    'baz'     => 'baz (EN) (dynamic)',
                    'another' => 'another (EN) (dynamic)'
                ]
            ],
            'fr' => [
                'jsmessages' => [
                    'bar'     => 'bar (FR) (dynamic)',
                    'another' => 'another (FR) (dynamic)'
                ],
                'another'    => [
                    'val1' => 'val1 (FR) (dynamic)'
                ]
            ]
        ]));
        $translator->rebuildCache();

        $result = $translator->getTranslations(['jsmessages', 'messages', 'another'], $locale);

        self::assertEquals($expected, $result);
    }

    public function getTranslationsWithDynamicTranslationsDataProvider(): array
    {
        return [
            [
                null,
                [
                    'messages'   => [
                        'foo' => 'foo messages (FR)'
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar'    => 'foobar (ES)',
                        'foo'       => 'foo (FR)',
                        'bar'       => 'bar (FR) (dynamic)',
                        'baz'       => 'baz (EN) (dynamic)',
                        'another'   => 'another (FR) (dynamic)'
                    ],
                    'another'    => [
                        'val1' => 'val1 (FR) (dynamic)'
                    ]
                ]
            ],
            [
                'fr',
                [
                    'messages'   => [
                        'foo' => 'foo messages (FR)'
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar'    => 'foobar (ES)',
                        'foo'       => 'foo (FR)',
                        'bar'       => 'bar (FR) (dynamic)',
                        'baz'       => 'baz (EN) (dynamic)',
                        'another'   => 'another (FR) (dynamic)'
                    ],
                    'another'    => [
                        'val1' => 'val1 (FR) (dynamic)'
                    ]
                ]
            ],
            [
                'en',
                [
                    'messages'   => [
                        'foo' => 'foo messages (EN)'
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar'    => 'foobar (ES)',
                        'foo'       => 'foo (EN) (dynamic)',
                        'bar'       => 'bar (EN)',
                        'baz'       => 'baz (EN) (dynamic)',
                        'another'   => 'another (EN) (dynamic)'
                    ],
                    'another'    => []
                ]
            ],
            [
                'es',
                [
                    'messages'   => [
                        'foo' => 'foo messages (ES)'
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foo'       => 'foo (PT-PT)',
                        'foobar'    => 'foobar (ES)',
                    ],
                    'another'    => []
                ]
            ],
        ];
    }

    public function testHasTrans(): void
    {
        $translator = $this->getTranslator(
            'en',
            $this->getStrategyProvider('en', array_keys(self::MESSAGES))
        );
        $translator->rebuildCache();

        $existingLabel = 'foo';
        self::assertTrue($translator->hasTrans($existingLabel));
        self::assertTrue($translator->hasTrans($existingLabel, 'jsmessages'));
        self::assertTrue($translator->hasTrans($existingLabel, 'jsmessages', 'en'));
        self::assertTrue($translator->hasTrans($existingLabel, 'jsmessages', 'fr'));

        $notExistingLabel = 'not_existing';
        self::assertFalse($translator->hasTrans($notExistingLabel));
        self::assertFalse($translator->hasTrans($notExistingLabel, 'jsmessages'));
        self::assertFalse($translator->hasTrans($notExistingLabel, 'jsmessages', 'en'));
        self::assertFalse($translator->hasTrans($notExistingLabel, 'jsmessages', 'fr'));
        self::assertFalse($translator->hasTrans(''));
    }

    public function testTrans(): void
    {
        $translator = $this->getTranslator(
            'en',
            $this->getStrategyProvider('en', array_keys(self::MESSAGES))
        );
        $translator->rebuildCache();

        $existingLabel = 'foo';
        self::assertEquals(
            self::MESSAGES['en']['messages'][$existingLabel],
            $translator->trans($existingLabel)
        );
        self::assertEquals(
            self::MESSAGES['en']['jsmessages'][$existingLabel],
            $translator->trans($existingLabel, [], 'jsmessages')
        );
        self::assertEquals(
            self::MESSAGES['en']['jsmessages'][$existingLabel],
            $translator->trans($existingLabel, [], 'jsmessages', 'en')
        );
        self::assertEquals(
            self::MESSAGES['fr']['jsmessages'][$existingLabel],
            $translator->trans($existingLabel, [], 'jsmessages', 'fr')
        );

        $notExistingLabel = 'not_existing';
        self::assertEquals($notExistingLabel, $translator->trans($notExistingLabel));
        self::assertEquals($notExistingLabel, $translator->trans($notExistingLabel, [], 'jsmessages'));
        self::assertEquals($notExistingLabel, $translator->trans($notExistingLabel, [], 'jsmessages', 'en'));
        self::assertEquals($notExistingLabel, $translator->trans($notExistingLabel, [], 'jsmessages', 'fr'));
        self::assertSame('', $translator->trans(''));
        self::assertSame('', $translator->trans(null));
    }

    public function testHasTransWithDynamicTranslations(): void
    {
        $dynamicTranslations = [
            'en' => [
                'jsmessages' => [
                    'foo' => 'foo (EN) (dynamic)'
                ]
            ],
            'fr' => [
                'jsmessages' => [
                    'foo' => 'foo (FR) (dynamic)',
                    'bar' => 'bar (FR) (dynamic)'
                ]
            ]
        ];

        $translator = $this->getTranslator(
            'en',
            $this->getStrategyProvider('en', array_keys(self::MESSAGES))
        );
        $translator->setDynamicTranslationProvider($this->getDynamicTranslationProvider($dynamicTranslations));
        $translator->rebuildCache();

        $existingLabel = 'foo';
        self::assertTrue($translator->hasTrans($existingLabel));
        self::assertTrue($translator->hasTrans($existingLabel, 'jsmessages'));
        self::assertTrue($translator->hasTrans($existingLabel, 'jsmessages', 'en'));
        self::assertTrue($translator->hasTrans($existingLabel, 'jsmessages', 'fr'));

        self::assertTrue($translator->hasTrans('bar', 'jsmessages', 'en'));
        self::assertTrue($translator->hasTrans('bar', 'jsmessages', 'fr'));

        $notExistingLabel = 'not_existing';
        self::assertFalse($translator->hasTrans($notExistingLabel));
        self::assertFalse($translator->hasTrans($notExistingLabel, 'jsmessages'));
        self::assertFalse($translator->hasTrans($notExistingLabel, 'jsmessages', 'en'));
        self::assertFalse($translator->hasTrans($notExistingLabel, 'jsmessages', 'fr'));
        self::assertFalse($translator->hasTrans(''));
    }

    public function testTransWithDynamicTranslations(): void
    {
        $dynamicTranslations = [
            'en' => [
                'jsmessages' => [
                    'foo' => 'foo (EN) (dynamic)'
                ],
                'validators' => [
                    'choice' =>
                        '{0} choice 0 (EN) (dynamic)|{1} choice 1 (EN) (dynamic)|]1,Inf] choice inf (EN) (dynamic)'
                ]
            ],
            'fr' => [
                'jsmessages' => [
                    'foo' => 'foo (FR) (dynamic)',
                    'bar' => 'bar (FR) (dynamic)'
                ]
            ]
        ];

        $translator = $this->getTranslator(
            'en',
            $this->getStrategyProvider('en', array_keys(self::MESSAGES))
        );
        $translator->setDynamicTranslationProvider($this->getDynamicTranslationProvider($dynamicTranslations));
        $translator->rebuildCache();

        $existingLabel = 'foo';
        self::assertEquals(
            self::MESSAGES['en']['messages'][$existingLabel],
            $translator->trans($existingLabel)
        );
        self::assertEquals(
            $dynamicTranslations['en']['jsmessages'][$existingLabel],
            $translator->trans($existingLabel, [], 'jsmessages')
        );
        self::assertEquals(
            $dynamicTranslations['en']['jsmessages'][$existingLabel],
            $translator->trans($existingLabel, [], 'jsmessages', 'en')
        );
        self::assertEquals(
            $dynamicTranslations['fr']['jsmessages'][$existingLabel],
            $translator->trans($existingLabel, [], 'jsmessages', 'fr')
        );

        self::assertEquals(
            self::MESSAGES['en']['jsmessages']['bar'],
            $translator->trans('bar', [], 'jsmessages', 'en')
        );
        self::assertEquals(
            $dynamicTranslations['fr']['jsmessages']['bar'],
            $translator->trans('bar', [], 'jsmessages', 'fr')
        );

        $choiceLabel = 'choice';
        self::assertEquals(
            'choice 0 (EN) (dynamic)',
            $translator->trans($choiceLabel, ['%count%' => 0], 'validators')
        );
        self::assertEquals(
            'choice 1 (EN) (dynamic)',
            $translator->trans($choiceLabel, ['%count%' => 1], 'validators')
        );
        self::assertEquals(
            'choice inf (EN) (dynamic)',
            $translator->trans($choiceLabel, ['%count%' => 2], 'validators')
        );

        $notExistingLabel = 'not_existing';
        self::assertEquals($notExistingLabel, $translator->trans($notExistingLabel));
        self::assertEquals($notExistingLabel, $translator->trans($notExistingLabel, [], 'jsmessages'));
        self::assertEquals($notExistingLabel, $translator->trans($notExistingLabel, [], 'jsmessages', 'en'));
        self::assertEquals($notExistingLabel, $translator->trans($notExistingLabel, [], 'jsmessages', 'fr'));
        self::assertSame('', $translator->trans(''));
        self::assertSame('', $translator->trans(null));
    }

    public function testTransForFallbackTranslation(): void
    {
        $locale = 'pt-PT';
        $domain = 'jsmessages';

        $translator = $this->getTranslator($locale, $this->getStrategyProvider($locale, ['en']));
        $translator->rebuildCache();

        self::assertTrue($translator->hasTrans('baz', $domain));
        self::assertEquals(
            self::MESSAGES['en'][$domain]['baz'],
            $translator->trans('baz', [], $domain, $locale)
        );

        self::assertTrue($translator->hasTrans('foo', $domain));
        self::assertEquals(
            self::MESSAGES[$locale][$domain]['foo'],
            $translator->trans('foo', [], $domain, $locale)
        );

        self::assertTrue($translator->hasTrans('foobarfoo', $domain));
        self::assertEquals(
            self::MESSAGES[$locale][$domain]['foobarfoo'],
            $translator->trans('foobarfoo', [], $domain, $locale)
        );
    }

    public function testGetCatalogue(): void
    {
        $locale = 'en_US';
        $strategyName = 'default';
        $fallbackLocales = ['en'];

        $strategy = $this->getStrategy($strategyName);

        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $strategyProvider->expects(self::any())
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects(self::exactly(2))
            ->method('getFallbackLocales')
            ->with(self::identicalTo($strategy))
            ->willReturnMap([
                [$strategy, $locale, $fallbackLocales],
                [$strategy, 'en', []]
            ]);
        $strategyProvider->expects(self::never())
            ->method('getAllFallbackLocales');

        $translator = $this->getTranslator($locale, $strategyProvider);

        self::assertEmpty($translator->getFallbackLocales());
        $catalogue = $translator->getCatalogue($locale);
        self::assertEquals($locale, $catalogue->getLocale());
        self::assertEquals([], $catalogue->all());
        self::assertEquals($fallbackLocales, $translator->getFallbackLocales());
    }

    public function testGetCatalogueStrategyChanged(): void
    {
        $firstStrategyName = 'first';
        $secondStrategyName = 'second';

        $firstStrategy = $this->getStrategy($firstStrategyName);
        $secondStrategy = $this->getStrategy($secondStrategyName);

        $strategyProvider = $this->getMockBuilder(TranslationStrategyProvider::class)
            ->setConstructorArgs([[$firstStrategy, $secondStrategy]])
            ->onlyMethods(['getAllFallbackLocales', 'getFallbackLocales'])
            ->getMock();
        $strategyProvider->expects(self::exactly(2))
            ->method('getFallbackLocales')
            ->willReturnMap([
                [$firstStrategy, 'en', []],
                [$secondStrategy, 'ru', []]
            ]);
        $strategyProvider->expects(self::never())
            ->method('getAllFallbackLocales');

        $translator = $this->getTranslator('en', $strategyProvider);

        $strategyProvider->setStrategy($firstStrategy);
        $translator->getCatalogue('en');
        self::assertEquals($firstStrategyName, ReflectionUtil::getPropertyValue($translator, 'appliedStrategyName'));
        self::assertCount(1, ReflectionUtil::getPropertyValue($translator, 'catalogues'));

        $strategyProvider->setStrategy($secondStrategy);
        $translator->getCatalogue('ru');
        self::assertEquals($secondStrategyName, ReflectionUtil::getPropertyValue($translator, 'appliedStrategyName'));
        self::assertCount(1, ReflectionUtil::getPropertyValue($translator, 'catalogues'));
    }
}
