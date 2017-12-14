<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\MessageCatalogue;

use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $messages = [
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
        $_locale = !is_null($locale) ? $locale : reset($locales);
        $fallbackLocales = array_slice($locales, array_search($_locale, $locales) + 1);
        $translator = $this->getTranslator(
            $this->getLoader(),
            $this->getStrategyProvider($_locale, $fallbackLocales)
        );
        $translator->setLocale($_locale);
        $result = $translator->getTranslations(array('jsmessages', 'validators'), $locale);

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

    /**
     * Create a catalog and fills it in with messages
     *
     * @param string $locale
     * @param array $dictionary
     * @return MessageCatalogue
     */
    public function getCatalogue($locale, $dictionary)
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
     * Creates a mock of Loader
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLoader()
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
     * @param string $locale
     * @param array  $fallbackLocales
     * @return TranslationStrategyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStrategyProvider($locale, array $fallbackLocales = [])
    {
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);

        $strategyProvider->expects($this->any())
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects($this->any())
            ->method('getFallbackLocales')
            ->willReturnCallback(function ($strategy, $loc) use ($locale, $fallbackLocales) {
                if ($loc === $locale) {
                    return $fallbackLocales;
                }

                return [];
            });

        return $strategyProvider;
    }

    /**
     * Creates a mock of Container
     *
     * @param LoaderInterface $loader
     * @param TranslationStrategyProvider $strategyProvider
     * @return ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContainer($loader, $strategyProvider)
    {
        $exceptionFlag = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
        $valueMap = [
            ['loader', $exceptionFlag, $loader],
            ['oro_translation.strategy.provider', $exceptionFlag, $strategyProvider]
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->willReturnMap($valueMap);

        return $container;
    }

    /**
     * Creates instance of Translator
     *
     * @param LoaderInterface $loader
     * @param TranslationStrategyProvider $strategyProvider
     * @param array $options
     * @return Translator
     */
    public function getTranslator($loader, $strategyProvider, $options = array())
    {
        $translator = new Translator(
            $this->getContainer($loader, $strategyProvider),
            new MessageSelector(),
            array('loader' => array('loader')),
            array_merge(['resource_files' => []], $options)
        );

        $translator->addResource('loader', 'foo', 'fr');
        $translator->addResource('loader', 'foo', 'en');
        $translator->addResource('loader', 'foo', 'es');
        $translator->addResource('loader', 'foo', 'pt-PT'); // European Portuguese
        $translator->addResource('loader', 'foo', 'pt_BR'); // Brazilian Portuguese

        return $translator;
    }

    public function testHasTrans()
    {
        $locale = 'en';
        $locales = array_keys($this->messages);
        $translator = $this->getTranslator($this->getLoader(), $this->getStrategyProvider($locale));

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

        $translator = $this->getTranslator($this->getLoader(), $this->getStrategyProvider($locale, $locales));
        $translator->setLocale($locale);
        $result = $translator->trans($translateKey, [], 'jsmessages', $locale);

        $this->assertTrue($translator->hasTrans($translateKey, 'jsmessages'));
        $this->assertEquals($message, $result);
    }

    public function testDynamicResourcesWithoutDatabaseTranslationMetadataCache()
    {
        $locale     = 'en';
        $container  = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->willReturn($this->getStrategyProvider($locale));
        $translator = $this->getMockBuilder(Translator::class)
                ->setConstructorArgs([$container, new MessageSelector(), [], ['resource_files' => []]])
                ->setMethods(['addResource'])
                ->getMock();
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

        $databaseCache = $this->createMock(DynamicTranslationMetadataCache::class);
        $translator = $this->getMockBuilder(Translator::class)
            ->setConstructorArgs([$container, new MessageSelector(), [], ['resource_files' => []]])
            ->setMethods(['addResource'])
            ->getMock();
        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);

        $translator->setLocale($locale);
        $translator->setDatabaseMetadataCache($databaseCache);

        $exceptionFlag = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
        $valueMap = [
            ['oro_translation.strategy.provider', $exceptionFlag, $this->getStrategyProvider($locale)],
            ['oro_translation.provider.translation_domain', $exceptionFlag, $translationDomainProvider],
        ];

        $container
            ->expects($this->any())
            ->method('hasParameter')
            ->with('installed')
            ->willReturn(true);
        $container
            ->expects($this->any())
            ->method('getParameter')
            ->with('installed')
            ->willReturn(true);
        $container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap($valueMap);

        $translationDomainProvider->expects($this->once())
            ->method('getAvailableDomainsForLocales')
            ->willReturn($domains);

        $translator->expects($this->exactly(count($domains)))->method('addResource');
        $translator->hasTrans('foo');
    }

    public function testGetCatalogue()
    {
        $locale = 'en_US';
        $strategyName = 'default';
        $allFallbackLocales = ['en'];
        $fallbackLocales = ['en'];

        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategy->expects($this->any())
            ->method('getName')
            ->willReturn($strategyName);

        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $strategyProvider->expects($this->any())
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects($this->any())
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

        $translator = $this->getTranslator($this->getLoader(), $strategyProvider);

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

        $firstStrategy = $this->createMock(TranslationStrategyInterface::class);
        $firstStrategy->expects($this->any())
            ->method('getName')
            ->willReturn($firstStrategyName);

        $secondStrategy = $this->createMock(TranslationStrategyInterface::class);
        $secondStrategy->expects($this->any())
            ->method('getName')
            ->willReturn($secondStrategyName);

        $strategyProvider = $this->getMockBuilder(TranslationStrategyProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllFallbackLocales', 'getFallbackLocales'])
            ->getMock();
        $strategyProvider->expects($this->any())
            ->method('getAllFallbackLocales')
            ->willReturn([]);
        $strategyProvider->expects($this->any())
            ->method('getFallbackLocales')
            ->willReturn([]);

        $strategyProvider->addStrategy($firstStrategy);
        $strategyProvider->addStrategy($secondStrategy);

        $translator = $this->getTranslator($this->getLoader(), $strategyProvider);

        $strategyProvider->setStrategy($firstStrategy);
        $translator->getCatalogue('en');
        $this->assertAttributeEquals($firstStrategyName, 'strategyName', $translator);
        $this->assertAttributeCount(1, 'catalogues', $translator);

        $strategyProvider->setStrategy($secondStrategy);
        $translator->getCatalogue('ru');
        $this->assertAttributeEquals($secondStrategyName, 'strategyName', $translator);
        $this->assertAttributeCount(1, 'catalogues', $translator);
    }
}
