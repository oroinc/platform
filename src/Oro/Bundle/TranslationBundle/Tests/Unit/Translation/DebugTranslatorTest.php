<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\DebugTranslator;
use Oro\Component\TestUtils\Mocks\ServiceLink;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DebugTranslatorTest extends \PHPUnit\Framework\TestCase
{
    protected $messages = array(
        'fr' => array(
            'jsmessages' => array(
                'foo' => 'foo (FR)',
            ),
            'messages' => array(
                'foo' => 'foo messages (FR)',
            ),
        ),
        'en' => array(
            'jsmessages' => array(
                'foo' => 'foo (EN)',
                'bar' => 'bar (EN)',
            ),
            'messages' => array(
                'foo' => 'foo messages (EN)',
            ),
            'validators' => array(
                'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
            ),
        ),
    );

    /**
     * @param string $locale
     * @param string $domain
     * @param string $source
     * @param string $expected
     * @dataProvider transDataProvider
     */
    public function testTrans($locale, $domain, $source, $expected)
    {
        $locales = array_keys($this->messages);
        foreach ($locales as $key => $value) {
            if ($value === $locale) {
                unset($locales[$key]);
            }
        }
        $translator = $this->getTranslator($this->getLoader(), $this->getStrategyProvider($locales));
        $_locale = !is_null($locale) ? $locale : reset($locales);
        $translator->setLocale($_locale);
        $translator->setFallbackLocales(array_slice($locales, array_search($_locale, $locales) + 1));

        $this->assertEquals($expected, $translator->trans($source, [], $domain));
    }

    /**
     * @return array
     */
    public function transDataProvider()
    {
        return [
            'translated' => [
                'locale' => 'en',
                'domain' => 'messages',
                'source' => 'foo',
                'expected' => '[foo messages (EN)]',
            ],
            'not translated' => [
                'locale' => 'fr',
                'domain' => 'jsmessages',
                'source' => 'baz',
                'expected' => '!!!---baz---!!!',
            ]
        ];
    }

    /**
     * @param string $locale
     * @param string $domain
     * @param string $source
     * @param int number
     * @param string $expected
     * @dataProvider transChoiceDataProvider
     */
    public function testTransChoice($locale, $domain, $source, $number, $expected)
    {
        $locales = array_keys($this->messages);
        foreach ($locales as $key => $value) {
            if ($value === $locale) {
                unset($locales[$key]);
            }
        }
        $translator = $this->getTranslator($this->getLoader(), $this->getStrategyProvider($locales));
        $_locale = !is_null($locale) ? $locale : reset($locales);
        $translator->setLocale($_locale);
        $translator->setFallbackLocales(array_slice($locales, array_search($_locale, $locales) + 1));

        $this->assertEquals($expected, $translator->transChoice($source, $number, [], $domain));
    }

    /**
     * @return array
     */
    public function transChoiceDataProvider()
    {
        return [
            'translated' => [
                'locale' => 'en',
                'domain' => 'validators',
                'source' => 'choice',
                'number' => 2,
                'expected' => '[choice inf (EN)]',
            ],
            'not translated' => [
                'locale' => 'fr',
                'domain' => 'validators',
                'source' => 'item',
                'number' => 1,
                'expected' => '!!!---item---!!!',
            ]
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
     * @return \PHPUnit\Framework\MockObject\MockObject
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
     * @param array $fallbackLocales
     * @return TranslationStrategyProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStrategyProvider(array $fallbackLocales = [])
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

    /**
     * Creates a mock of Container
     *
     * @param LoaderInterface $loader
     * @param TranslationStrategyProvider $strategyProvider
     * @return ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
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
     * @param $loader
     * @param array $options
     * @return DebugTranslator
     */
    public function getTranslator($loader, $strategyProvider, $options = array())
    {
        $translator = new DebugTranslator(
            $this->getContainer($loader, $strategyProvider),
            new MessageFormatter(),
            array('loader' => array('loader')),
            array_merge(['resource_files' => []], $options)
        );

        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategyProvider = $this->createMock(TranslationStrategyProvider::class);
        $strategyProviderLink = new ServiceLink($strategyProvider);
        $strategyProvider->expects($this->any())
            ->method('getStrategy')
            ->willReturn($strategy);
        $strategyProvider->expects($this->any())
            ->method('getFallbackLocales')
            ->willReturn([]);

        $translator->setStrategyProviderLink($strategyProviderLink);

        /** @var TranslationDomainProvider|\PHPUnit\Framework\MockObject\MockObject $translationDomainProvider */
        $translationDomainProvider = $this->createMock(TranslationDomainProvider::class);
        $translator->setTranslationDomainProvider($translationDomainProvider);

        $translator->addResource('loader', 'foo', 'fr');
        $translator->addResource('loader', 'foo', 'en');

        return $translator;
    }
}
