<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\DebugTranslator;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DebugTranslatorTest extends \PHPUnit\Framework\TestCase
{
    private array $messages = [
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
            ],
            'messages' => [
                'foo' => 'foo messages (EN)',
            ],
            'validators' => [
                'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
            ],
        ],
    ];

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
        foreach ($locales as $key => $value) {
            if ($value === $locale) {
                unset($locales[$key]);
            }
        }
        $translator = $this->getTranslator($this->getLoader(), $this->getStrategyProvider($locales));
        $locale = $locale ?: reset($locales);
        $translator->setLocale($locale);
        $translator->setFallbackLocales(array_slice($locales, array_search($locale, $locales, true) + 1));

        $this->assertEquals($expected, $translator->trans($source, $parameters, $domain));
    }

    public function transDataProvider(): array
    {
        return [
            'translated' => [
                'locale' => 'en',
                'domain' => 'messages',
                'source' => 'foo',
                'parameters' => [],
                'expected' => '[foo messages (EN)]',
            ],
            'not translated' => [
                'locale' => 'fr',
                'domain' => 'jsmessages',
                'source' => 'baz',
                'parameters' => [],
                'expected' => '!!!---baz---!!!',
            ],
            'translated choice' => [
                'locale' => 'en',
                'domain' => 'validators',
                'source' => 'choice',
                'parameters' => ['%count%' => 2],
                'expected' => '[choice inf (EN)]',
            ],
            'not translated choice' => [
                'locale' => 'fr',
                'domain' => 'validators',
                'source' => 'item',
                'parameters' => ['%count%' => 1],
                'expected' => '!!!---item---!!!',
            ]
        ];
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

    private function getStrategyProvider(array $fallbackLocales = []): TranslationStrategyProvider
    {
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);

        $strategyProvider->expects($this->any())
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects($this->any())
            ->method('getFallbackLocales')
            ->willReturnCallback(function ($strategy, $locale) use ($fallbackLocales) {
                if ('en' !== $locale) {
                    return $fallbackLocales;
                }

                return [];
            });

        return $strategyProvider;
    }

    private function getTranslator(
        LoaderInterface $loader,
        TranslationStrategyProvider $strategyProvider,
        array $options = []
    ): DebugTranslator {
        $container = TestContainerBuilder::create()
            ->add('loader', $loader)
            ->add('oro_translation.strategy.provider', $strategyProvider)
            ->getContainer($this);

        $translator = new DebugTranslator(
            $container,
            new MessageFormatter(),
            'en',
            ['loader' => ['loader']],
            array_merge(['resource_files' => []], $options)
        );

        $translator->setStrategyProvider($strategyProvider);

        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);
        $translator->setTranslationDomainProvider($translationDomainProvider);

        $translator->addResource('loader', 'foo', 'fr');
        $translator->addResource('loader', 'foo', 'en');

        return $translator;
    }
}
