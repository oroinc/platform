<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\TranslationBundle\Event\AfterCatalogueDump;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $messages = [
        'fr' => [
            'jsmessages' => [
                'foo' => 'foo (FR)',
            ],
            'messages' => [
                'foo' => 'foo messages (FR)',
            ],
        ],
        'en' => [
            'jsmessages' => [
                'foo' => 'foo (EN)',
                'bar' => 'bar (EN)',
                'baz' => 'baz (EN)',
            ],
            'messages' => [
                'foo' => 'foo messages (EN)',
            ],
            'validators' => [
                'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
            ],
        ],
        'es' => [
            'jsmessages' => [
                'foobar' => 'foobar (ES)',
            ],
            'messages' => [
                'foo' => 'foo messages (ES)',
            ],
        ],
        'pt-PT' => [
            'jsmessages' => [
                'foobarfoo' => 'foobarfoo (PT-PT)',
            ],
        ],
        'pt_BR' => [
            'validators' => [
                'other choice' =>
                    '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
            ],
        ],
    ];

    /**
     * @dataProvider dataProviderGetTranslations
     */
    public function testGetTranslations(?string $locale, array $expected)
    {
        $locales = array_keys($this->messages);
        $_locale = $locale ?? reset($locales);
        $fallbackLocales = \array_slice($locales, \array_search($_locale, $locales, true) + 1);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->getTranslator(
            $this->getLoader(),
            $this->getStrategyProvider($_locale, $fallbackLocales),
            $eventDispatcher
        );
        $translator->setLocale($_locale);

        $result = $translator->getTranslations(['jsmessages', 'validators'], $locale);

        $this->assertEquals($expected, $result);
    }

    public function dataProviderGetTranslations(): array
    {
        return [
            [
                null,
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                        'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar' => 'foobar (ES)',
                        'foo' => 'foo (FR)',
                        'bar' => 'bar (EN)',
                        'baz' => 'baz (EN)',
                    ],
                ]
            ],
            [
                'fr',
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                        'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar' => 'foobar (ES)',
                        'foo' => 'foo (FR)',
                        'bar' => 'bar (EN)',
                        'baz' => 'baz (EN)',
                    ],
                ]
            ],
            [
                'en',
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                        'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
                    ],
                    'jsmessages' => [
                        'foobarfoo' => 'foobarfoo (PT-PT)',
                        'foobar' => 'foobar (ES)',
                        'foo' => 'foo (EN)',
                        'bar' => 'bar (EN)',
                        'baz' => 'baz (EN)',
                    ],
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
                        'foobar' => 'foobar (ES)',
                    ],
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
                    ],
                ]
            ],
            [
                'pt_BR',
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)',
                    ],
                ]
            ],
        ];
    }

    public function testGetTranslationsWhenStrategyChanged()
    {
        $locale = 'pt_BR';
        $expectedMessages = $this->messages[$locale];
        $fallbackLocales = [];

        $strategyName = 'sampleStrategy';

        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategy->expects($this->exactly(2))
            ->method('getName')
            ->willReturn($strategyName);

        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $strategyProvider->expects($this->any())
            ->method('getFallbackLocales')
            ->with($strategy)
            ->willReturnCallback(function ($strategy, $loc) use ($locale, $fallbackLocales) {
                if ($loc === $locale) {
                    return $fallbackLocales;
                }

                return [];
            });
        $strategyProvider->expects($this->exactly(3))
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects($this->once())
            ->method('getAllFallbackLocales')
            ->with($strategy)
            ->willReturn($fallbackLocales);

        $translator = $this->getTranslator(
            $this->getLoader(),
            $strategyProvider,
            $this->getEventDispatcher(
                $locale,
                [
                    'validators' => [
                        'other choice' =>
                            '{0} other choice 0 (PT-BR)|{1} other choice 1 (PT-BR)|]1,Inf] other choice inf (PT-BR)'
                    ],
                ]
            )
        );
        $translator->setLocale($locale);

        $result = $translator->getTranslations(['validators'], $locale);

        $this->assertEquals($expectedMessages, $result);
    }

    public function testHasTrans()
    {
        $locale = 'en';
        $locales = array_keys($this->messages);
        $translator = $this->getTranslator(
            $this->getLoader(),
            $this->getStrategyProvider($locale),
            $this->getEventDispatcher(
                $locale,
                [
                    'jsmessages' => ['foo' => 'foo (EN)', 'bar' => 'bar (EN)', 'baz' => 'baz (EN)'],
                    'messages' => ['foo' => 'foo messages (EN)'],
                    'validators' => ['choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)'],
                ]
            )
        );

        $translator->setLocale($locale);
        $translator->setFallbackLocales($locales);

        $this->assertTrue($translator->hasTrans('foo', 'jsmessages', $locale));
        $this->assertTrue($translator->hasTrans('foo'));

        $this->assertFalse($translator->hasTrans('foo11111'));
    }

    public function testGetFallbackTranslations()
    {
        $locale = 'pt-PT';
        $locales = array_keys($this->messages);
        foreach ($locales as $key => $value) {
            if ($value === $locale) {
                unset($locales[$key]);
            }
        }

        $translateKey = 'baz';
        $message = $this->messages['en']['jsmessages'][$translateKey];

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->getTranslator(
            $this->getLoader(),
            $this->getStrategyProvider($locale, $locales),
            $eventDispatcher
        );
        $translator->setLocale($locale);
        $result = $translator->trans($translateKey, [], 'jsmessages', $locale);

        $this->assertTrue($translator->hasTrans($translateKey, 'jsmessages'));
        $this->assertEquals($message, $result);
    }

    public function testDynamicResourcesWithoutDatabaseTranslationMetadataCache()
    {
        $locale = 'en';
        $container  = $this->createMock(ContainerInterface::class);
        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);
        $applicationState = $this->createMock(ApplicationState::class);

        $strategyProvider = $this->getStrategyProvider($locale);

        $translator = $this->getMockBuilder(Translator::class)
            ->setConstructorArgs([
                $container,
                new MessageFormatter(),
                'en',
                [],
                ['resource_files' => []]
            ])
            ->onlyMethods(['addResource'])
            ->getMock();

        $translator->setTranslationDomainProvider($translationDomainProvider);
        $translator->setStrategyProvider($strategyProvider);

        $applicationState->method('isInstalled')->willReturn(true);

        $translator->setApplicationState($applicationState);
        $translator->setEventDispatcher($this->getEventDispatcher());

        $translator->setLocale($locale);

        $translator->expects($this->never())
            ->method('addResource');

        $translator->hasTrans('foo');
    }

    public function testLoadingOfDynamicResources()
    {
        $locale = 'en';
        $domains = [
            ['code' => $locale, 'domain' => 'domain1'],
            ['code' => $locale, 'domain' => 'domain2'],
            ['code' => $locale, 'domain' => 'domain3'],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $applicationState = $this->createMock(ApplicationState::class);
        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);
        $translationDomainProvider->expects($this->once())
            ->method('getAvailableDomainsForLocales')
            ->willReturn($domains);
        $strategyProvider = $this->getStrategyProvider($locale);

        $databaseCache = $this->createMock(DynamicTranslationMetadataCache::class);

        $translator = $this->getMockBuilder(Translator::class)
            ->setConstructorArgs([
                $container,
                new MessageFormatter(),
                'en',
                [],
                ['resource_files' => []]
            ])
            ->onlyMethods(['addResource'])
            ->getMock();

        $translator->setTranslationDomainProvider($translationDomainProvider);
        $translator->setStrategyProvider($strategyProvider);
        $translator->setEventDispatcher($this->getEventDispatcher());
        $applicationState->method('isInstalled')->willReturn(true);

        $translator->setApplicationState($applicationState);

        $translator->setLocale($locale);
        $translator->setDatabaseMetadataCache($databaseCache);

        $translator->expects($this->exactly(count($domains)))
            ->method('addResource');

        $translator->hasTrans('foo');

        // To ensure that addResource is not called again.
        $translator->hasTrans('bar');
    }

    public function testGetCatalogue()
    {
        $locale = 'en_US';
        $strategyName = 'default';
        $allFallbackLocales = ['en'];
        $fallbackLocales = ['en'];

        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategy->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($strategyName);

        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $strategyProvider->expects(self::exactly(4))
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects(self::once())
            ->method('getAllFallbackLocales')
            ->with($strategy)
            ->willReturn($allFallbackLocales);
        $strategyProvider->expects($this->any())
            ->method('getFallbackLocales')
            ->with($strategy)
            ->willReturnCallback(function ($strategy, $loc) use ($locale, $fallbackLocales) {
                if ($loc === $locale) {
                    return $fallbackLocales;
                }

                return [];
            });

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->getTranslator($this->getLoader(), $strategyProvider, $eventDispatcher);

        self::assertEmpty($translator->getFallbackLocales());
        $translator->getCatalogue($locale);
        self::assertEquals($allFallbackLocales, $translator->getFallbackLocales());
    }

    public function testGetCatalogueStrategyChanged()
    {
        $firstStrategyName = 'first';
        $secondStrategyName = 'second';

        $firstStrategy = $this->createMock(TranslationStrategyInterface::class);
        $firstStrategy->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($firstStrategyName);

        $secondStrategy = $this->createMock(TranslationStrategyInterface::class);
        $secondStrategy->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($secondStrategyName);

        $strategyProvider = $this->getMockBuilder(TranslationStrategyProvider::class)
            ->setConstructorArgs([[$firstStrategy, $secondStrategy]])
            ->onlyMethods(['getAllFallbackLocales', 'getFallbackLocales'])
            ->getMock();
        $strategyProvider->expects(self::exactly(2))
            ->method('getAllFallbackLocales')
            ->willReturn([]);
        $strategyProvider->expects(self::exactly(2))
            ->method('getFallbackLocales')
            ->willReturn([]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->getTranslator($this->getLoader(), $strategyProvider, $eventDispatcher);

        $strategyProvider->setStrategy($firstStrategy);
        $translator->getCatalogue('en');
        self::assertEquals($firstStrategyName, ReflectionUtil::getPropertyValue($translator, 'strategyName'));
        self::assertCount(1, ReflectionUtil::getPropertyValue($translator, 'catalogues'));

        $strategyProvider->setStrategy($secondStrategy);
        $translator->getCatalogue('ru');
        self::assertEquals($secondStrategyName, ReflectionUtil::getPropertyValue($translator, 'strategyName'));
        self::assertCount(1, ReflectionUtil::getPropertyValue($translator, 'catalogues'));
    }

    private function getTranslator(
        LoaderInterface $loader,
        TranslationStrategyProvider $strategyProvider,
        EventDispatcherInterface $eventDispatcher,
        array $options = []
    ): Translator {
        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);
        $applicationState = $this->createMock(ApplicationState::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->with('loader')
            ->willReturn($loader);

        $translator = new Translator(
            $container,
            new MessageFormatter(),
            'en',
            ['loader' => ['loader']],
            array_merge(['resource_files' => []], $options)
        );

        $translator->setTranslationDomainProvider($translationDomainProvider);
        $translator->setStrategyProvider($strategyProvider);
        $applicationState->method('isInstalled')->willReturn(true);

        $translator->setApplicationState($applicationState);
        $translator->setEventDispatcher($eventDispatcher);

        $translator->addResource('loader', 'foo', 'fr');
        $translator->addResource('loader', 'foo', 'en');
        $translator->addResource('loader', 'foo', 'es');
        $translator->addResource('loader', 'foo', 'pt-PT'); // European Portuguese
        $translator->addResource('loader', 'foo', 'pt_BR'); // Brazilian Portuguese

        return $translator;
    }

    private function getLoader(): LoaderInterface
    {
        $messages = $this->messages;
        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->any())
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
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);

        $strategyProvider->expects($this->any())
            ->method('getStrategy')
            ->willReturn($strategy);

        $strategyProvider->expects($this->any())
            ->method('getFallbackLocales')
            ->with($strategy)
            ->willReturnCallback(function ($strategy, $loc) use ($locale, $fallbackLocales) {
                if ($loc === $locale) {
                    return $fallbackLocales;
                }

                return [];
            });

        return $strategyProvider;
    }

    private function getEventDispatcher(
        string $expectedLocale = 'en',
        array $expectedMessages = []
    ): EventDispatcherInterface {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new AfterCatalogueDump(
                    new MessageCatalogue($expectedLocale, $expectedMessages)
                ),
                AfterCatalogueDump::NAME
            );

        return $eventDispatcher;
    }
}
