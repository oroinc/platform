<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Event\AfterCatalogueDump;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\TestUtils\Mocks\ServiceLink;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
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
     * @param string $locale
     * @param array $expected
     */
    public function testGetTranslations($locale, array $expected)
    {
        $locales = array_keys($this->messages);
        $_locale = $locale ?? reset($locales);
        $fallbackLocales = \array_slice($locales, \array_search($_locale, $locales, true) + 1);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
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

    /**
     * @return array
     */
    public function dataProviderGetTranslations()
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

        /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject $strategy */
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategy
            ->expects($this->exactly(2))
            ->method('getName')
            ->willReturn($strategyName);

        /** @var TranslationStrategyProvider|\PHPUnit\Framework\MockObject\MockObject $strategyProvider */
        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $this->mockStrategyProviderFallbackLocales($strategyProvider, $strategy, $locale, $fallbackLocales);
        $strategyProvider
            ->expects($this->exactly(3))
            ->method('getStrategy')
            ->willReturn($strategy);

        $strategyProvider
            ->expects($this->once())
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

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
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
        /** @var TranslationDomainProvider|\PHPUnit\Framework\MockObject\MockObject $translationDomainProvider */
        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);
        $strategyProvider = $this->getStrategyProvider($locale);
        $strategyProviderLink = new ServiceLink($strategyProvider);

        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->getMockBuilder(Translator::class)
            ->setConstructorArgs([
                $container,
                new MessageFormatter(),
                'en',
                [],
                ['resource_files' => []]
            ])
            ->setMethods(['addResource'])
            ->getMock();

        $translator->setTranslationDomainProvider($translationDomainProvider);
        $translator->setStrategyProviderLink($strategyProviderLink);
        $translator->setInstalled(true);
        $translator->setEventDispatcher($this->getEventDispatcher());

        $translator->setLocale($locale);

        $translator->expects($this->never())->method('addResource');
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
        /** @var TranslationDomainProvider|\PHPUnit\Framework\MockObject\MockObject $translationDomainProvider */
        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);
        $translationDomainProvider->expects($this->once())
            ->method('getAvailableDomainsForLocales')
            ->willReturn($domains);
        $strategyProvider = $this->getStrategyProvider($locale);
        $strategyProviderLink = new ServiceLink($strategyProvider);

        /** @var DynamicTranslationMetadataCache|\PHPUnit\Framework\MockObject\MockObject $databaseCache */
        $databaseCache = $this->createMock(DynamicTranslationMetadataCache::class);

        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->getMockBuilder(Translator::class)
            ->setConstructorArgs([
                $container,
                new MessageFormatter(),
                'en',
                [],
                ['resource_files' => []]
            ])
            ->setMethods(['addResource'])
            ->getMock();

        $translator->setTranslationDomainProvider($translationDomainProvider);
        $translator->setStrategyProviderLink($strategyProviderLink);
        $translator->setEventDispatcher($this->getEventDispatcher());
        $translator->setInstalled(true);

        $translator->setLocale($locale);
        $translator->setDatabaseMetadataCache($databaseCache);

        $translator->expects($this->exactly(count($domains)))->method('addResource');
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
        $strategy->expects($this->exactly(2))
            ->method('getName')
            ->willReturn($strategyName);

        /** @var TranslationStrategyProvider|\PHPUnit\Framework\MockObject\MockObject $strategyProvider */
        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $strategyProvider->expects($this->exactly(4))
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects($this->once())
            ->method('getAllFallbackLocales')
            ->with($strategy)
            ->willReturn($allFallbackLocales);
        $this->mockStrategyProviderFallbackLocales($strategyProvider, $strategy, $locale, $fallbackLocales);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->getTranslator($this->getLoader(), $strategyProvider, $eventDispatcher);

        $this->assertAttributeEmpty('strategyName', $translator);
        $this->assertEmpty($translator->getFallbackLocales());
        $this->assertAttributeEmpty('catalogues', $translator);

        $translator->getCatalogue($locale);

        $this->assertAttributeEquals($strategyName, 'strategyName', $translator);
        $this->assertEquals($allFallbackLocales, $translator->getFallbackLocales());
        $this->assertAttributeCount(2, 'catalogues', $translator); // en and en_US
    }

    public function testGetCatalogueStrategyChanged()
    {
        $firstStrategyName = 'first';
        $secondStrategyName = 'second';

        /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject $firstStrategy */
        $firstStrategy = $this->createMock(TranslationStrategyInterface::class);
        $firstStrategy->expects($this->exactly(3))
            ->method('getName')
            ->willReturn($firstStrategyName);

        /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject $secondStrategy */
        $secondStrategy = $this->createMock(TranslationStrategyInterface::class);
        $secondStrategy->expects($this->exactly(3))
            ->method('getName')
            ->willReturn($secondStrategyName);

        /** @var TranslationStrategyProvider|\PHPUnit\Framework\MockObject\MockObject $strategyProvider */
        $strategyProvider = $this->getMockBuilder(TranslationStrategyProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllFallbackLocales', 'getFallbackLocales'])
            ->getMock();
        $strategyProvider->expects($this->exactly(2))
            ->method('getAllFallbackLocales')
            ->willReturn([]);
        $strategyProvider->expects($this->exactly(2))
            ->method('getFallbackLocales')
            ->willReturn([]);

        $strategyProvider->addStrategy($firstStrategy);
        $strategyProvider->addStrategy($secondStrategy);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->getTranslator($this->getLoader(), $strategyProvider, $eventDispatcher);

        $strategyProvider->setStrategy($firstStrategy);
        $translator->getCatalogue('en');
        $this->assertAttributeEquals($firstStrategyName, 'strategyName', $translator);
        $this->assertAttributeCount(1, 'catalogues', $translator);

        $strategyProvider->setStrategy($secondStrategy);
        $translator->getCatalogue('ru');
        $this->assertAttributeEquals($secondStrategyName, 'strategyName', $translator);
        $this->assertAttributeCount(1, 'catalogues', $translator);
    }

    /**
     * Creates instance of Translator
     *
     * @param LoaderInterface $loader
     * @param TranslationStrategyProvider $strategyProvider
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $options
     * @return Translator
     */
    private function getTranslator(
        LoaderInterface $loader,
        TranslationStrategyProvider $strategyProvider,
        EventDispatcherInterface $eventDispatcher,
        array $options = []
    ) {
        $strategyProviderServiceLink = new ServiceLink($strategyProvider);

        /** @var TranslationDomainProvider|\PHPUnit\Framework\MockObject\MockObject $translationDomainProvider */
        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);

        $translator = new Translator(
            $this->getContainer($loader),
            new MessageFormatter(),
            'en',
            ['loader' => ['loader']],
            array_merge(['resource_files' => []], $options)
        );

        $translator->setTranslationDomainProvider($translationDomainProvider);
        $translator->setStrategyProviderLink($strategyProviderServiceLink);
        $translator->setInstalled(true);
        $translator->setEventDispatcher($eventDispatcher);

        $translator->addResource('loader', 'foo', 'fr');
        $translator->addResource('loader', 'foo', 'en');
        $translator->addResource('loader', 'foo', 'es');
        $translator->addResource('loader', 'foo', 'pt-PT'); // European Portuguese
        $translator->addResource('loader', 'foo', 'pt_BR'); // Brazilian Portuguese

        return $translator;
    }

    /**
     * Creates a mock of Container
     *
     * @param LoaderInterface $loader
     * @return ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getContainer($loader)
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->with('loader')
            ->willReturn($loader);

        return $container;
    }

    /**
     * Creates a mock of Loader
     *
     * @return LoaderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getLoader()
    {
        $messages = $this->messages;
        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->any())
            ->method('load')
            ->willReturnCallback(function ($resource, $locale, $domain) use ($messages) {
                return $this->getCatalogue($locale, $messages[$locale]);
            });

        return $loader;
    }

    /**
     * Create a catalog and fills it in with messages
     *
     * @param string $locale
     * @param array $dictionary
     * @return MessageCatalogue
     */
    private function getCatalogue($locale, $dictionary)
    {
        $catalogue = new MessageCatalogue($locale);
        foreach ($dictionary as $domain => $messages) {
            foreach ($messages as $key => $translation) {
                $catalogue->set($key, $translation, $domain);
            }
        }

        return $catalogue;
    }

    /**
     * @param string $locale
     * @param array  $fallbackLocales
     * @return TranslationStrategyProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getStrategyProvider($locale, array $fallbackLocales = [])
    {
        /** @var TranslationStrategyInterface $strategy */
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);

        $strategyProvider->expects($this->any())
            ->method('getStrategy')
            ->willReturn($strategy);

        $this->mockStrategyProviderFallbackLocales($strategyProvider, $strategy, $locale, $fallbackLocales);

        return $strategyProvider;
    }

    /**
     * @param TranslationStrategyProvider|\PHPUnit\Framework\MockObject\MockObject $strategyProvider
     * @param TranslationStrategyInterface $strategy
     * @param string $locale
     * @param array $fallbackLocales
     */
    private function mockStrategyProviderFallbackLocales(
        $strategyProvider,
        TranslationStrategyInterface $strategy,
        string $locale,
        array $fallbackLocales = []
    ) {
        $strategyProvider
            ->expects($this->any())
            ->method('getFallbackLocales')
            ->with($strategy)
            ->willReturnCallback(function ($strategy, $loc) use ($locale, $fallbackLocales) {
                if ($loc === $locale) {
                    return $fallbackLocales;
                }

                return [];
            });
    }

    /**
     * @param string $expectedLocale
     * @param array $expectedMessages
     * @return \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private function getEventDispatcher(string $expectedLocale = 'en', array $expectedMessages = [])
    {
        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                AfterCatalogueDump::NAME,
                new AfterCatalogueDump(
                    new MessageCatalogue($expectedLocale, $expectedMessages)
                )
            );

        return $eventDispatcher;
    }
}
