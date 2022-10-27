<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\DebugTranslator;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationProvider;
use Oro\Bundle\TranslationBundle\Translation\MessageCatalogueSanitizer;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizationErrorCollection;
use Oro\Component\Testing\TempDirExtension;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DebugTranslatorTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private array $messages = [
        'fr' => [
            'jsmessages' => [
                'foo' => 'foo (FR)',
            ],
            'messages'   => [
                'foo' => 'foo messages (FR)',
            ],
        ],
        'en' => [
            'jsmessages' => [
                'foo' => 'foo (EN)',
                'bar' => 'bar (EN)',
            ],
            'messages'   => [
                'foo' => 'foo messages (EN)',
            ],
            'validators' => [
                'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
            ],
        ],
    ];

    private function getTranslator(array $fallbackLocales = []): DebugTranslator
    {
        $cacheDir = $this->getTempDir('debug_translator');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::atLeastOnce())
            ->method('get')
            ->with('loader')
            ->willReturn($this->getLoader());

        $translator = new DebugTranslator(
            $container,
            new MessageFormatter(),
            'en',
            ['loader' => ['loader']],
            ['resource_files' => [], 'cache_dir' => $cacheDir]
        );

        $translator->setStrategyProvider($this->getStrategyProvider($fallbackLocales));
        $translator->setResourceCache(new MemoryCache());
        $translator->setMessageCatalogueSanitizer($this->createMock(MessageCatalogueSanitizer::class));
        $translator->setSanitizationErrorCollection(new TranslationMessageSanitizationErrorCollection());
        $translator->setDynamicTranslationProvider(new DynamicTranslationProvider(
            new DynamicTranslationLoaderStub(),
            $this->createMock(DynamicTranslationCache::class)
        ));

        $translator->addResource('loader', 'foo.fr.loader', 'fr');
        $translator->addResource('loader', 'foo.en.loader', 'en');

        return $translator;
    }

    private function getLoader(): LoaderInterface
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects(self::any())
            ->method('load')
            ->willReturnCallback(function ($resource, $locale) {
                return $this->getCatalogue($locale, $this->messages[$locale]);
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

    private function getStrategyProvider(array $fallbackLocales = []): TranslationStrategyProvider
    {
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategy->expects(self::any())
            ->method('getName')
            ->willReturn('test_strategy');

        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $strategyProvider->expects(self::any())
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects(self::any())
            ->method('getStrategies')
            ->willReturn([$strategy]);
        $strategyProvider->expects(self::any())
            ->method('getFallbackLocales')
            ->willReturn([]);
        $strategyProvider->expects(self::any())
            ->method('getAllFallbackLocales')
            ->willReturn($fallbackLocales);

        return $strategyProvider;
    }

    /**
     * @dataProvider transDataProvider
     */
    public function testTrans(
        string $locale,
        string $domain,
        string $source,
        array $parameters,
        string $expected
    ): void {
        $locales = array_keys($this->messages);
        $translator = $this->getTranslator($locales);
        $locale = $locale ?: reset($locales);
        $translator->setLocale($locale);
        $translator->rebuildCache();

        self::assertEquals($expected, $translator->trans($source, $parameters, $domain));
    }

    public function transDataProvider(): array
    {
        return [
            'translated'            => [
                'locale'     => 'en',
                'domain'     => 'messages',
                'source'     => 'foo',
                'parameters' => [],
                'expected'   => '[foo messages (EN)]',
            ],
            'not translated'        => [
                'locale'     => 'fr',
                'domain'     => 'jsmessages',
                'source'     => 'baz',
                'parameters' => [],
                'expected'   => '!!!---baz---!!!',
            ],
            'translated choice'     => [
                'locale'     => 'en',
                'domain'     => 'validators',
                'source'     => 'choice',
                'parameters' => ['%count%' => 2],
                'expected'   => '[choice inf (EN)]',
            ],
            'not translated choice' => [
                'locale'     => 'fr',
                'domain'     => 'validators',
                'source'     => 'item',
                'parameters' => ['%count%' => 2],
                'expected'   => '!!!---item---!!!',
            ]
        ];
    }
}
