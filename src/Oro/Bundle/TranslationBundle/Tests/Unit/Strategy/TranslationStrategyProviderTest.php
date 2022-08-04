<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationStrategyProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultStrategy;

    /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $customStrategy;

    /** @var TranslationStrategyProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->defaultStrategy = $this->createMock(TranslationStrategyInterface::class);
        $this->defaultStrategy->expects($this->any())
            ->method('isApplicable')
            ->willReturn(true);
        $this->defaultStrategy->expects($this->any())
            ->method('getName')
            ->willReturn('default');

        $this->customStrategy = $this->createMock(TranslationStrategyInterface::class);
        $this->customStrategy->expects($this->any())
            ->method('isApplicable')
            ->willReturn(true);
        $this->customStrategy->expects($this->any())
            ->method('getName')
            ->willReturn('custom');

        $this->provider = new TranslationStrategyProvider([$this->defaultStrategy, $this->customStrategy]);
    }

    public function testGetStrategy()
    {
        $this->assertEquals($this->defaultStrategy, $this->provider->getStrategy());
    }

    public function testSetStrategy()
    {
        $this->assertSame($this->defaultStrategy, $this->provider->getStrategy());

        $this->provider->setStrategy($this->customStrategy);

        $this->assertSame($this->customStrategy, $this->provider->getStrategy());
    }

    public function testGetStrategies()
    {
        $this->assertSame(
            [
                'default' => $this->defaultStrategy,
                'custom' => $this->customStrategy
            ],
            $this->provider->getStrategies()
        );
    }

    /**
     * @dataProvider getFallbackLocalesDataProvider
     */
    public function testGetFallbackLocales(
        array $fallbackTree,
        string $locale,
        array $expectedFallbackLocales
    ) {
        $defaultStrategy = $this->createMock(TranslationStrategyInterface::class);

        $provider = new TranslationStrategyProvider([$defaultStrategy]);

        $testedStrategy = $this->createMock(TranslationStrategyInterface::class);
        $testedStrategy->expects($this->any())
            ->method('getLocaleFallbacks')
            ->willReturn($fallbackTree);

        $this->assertEquals($expectedFallbackLocales, $provider->getFallbackLocales($testedStrategy, $locale));
    }

    public function getFallbackLocalesDataProvider(): array
    {
        return [
            'one node tree defined locale' => [
                'fallbackTree' => [
                    'en' => [],
                ],
                'locale' => 'en',
                'expectedFallbackLocales' => [],
            ],
            'one node tree undefined locale' => [
                'fallbackTree' => [
                    'en' => [],
                ],
                'locale' => 'ru',
                'expectedFallbackLocales' => [Translator::DEFAULT_LOCALE],
            ],
            'complex tree defined locale first level' => [
                'fallbackTree' => [
                    'en' => [
                        'en_US' => [
                            'en_CA' => [],
                            'en_MX' => [],
                        ],
                        'en_GB' => [],
                    ],
                    'ru' => [
                        'ru_RU' => [],
                        'ru_UA' => [],
                    ],
                ],
                'locale' => 'ru',
                'expectedFallbackLocales' => [],
            ],
            'complex tree defined locale second level' => [
                'fallbackTree' => [
                    'en' => [
                        'en_US' => [
                            'en_CA' => [],
                            'en_MX' => [],
                        ],
                        'en_GB' => [],
                    ],
                    'ru' => [
                        'ru_RU' => [],
                        'ru_UA' => [],
                    ],
                ],
                'locale' => 'ru_RU',
                'expectedFallbackLocales' => ['ru'],
            ],
            'complex tree defined locale third level' => [
                'fallbackTree' => [
                    'en' => [
                        'en_US' => [
                            'en_CA' => [],
                            'en_MX' => [],
                        ],
                        'en_GB' => [],
                    ],
                    'ru' => [
                        'ru_RU' => [],
                        'ru_UA' => [],
                    ],
                ],
                'locale' => 'en_MX',
                'expectedFallbackLocales' => ['en_US', 'en'],
            ],
            'localization based fallback tree' => [
                'fallbackTree' => [
                    'en' => [//Default Localization's Language
                        'ru' => [//Localization1 Language
                            'en' => [//Localization2 Language
                                'en' => [//Localization3 Language
                                    'en' => [//Localization4 Language
                                        'pl' => [//Localization5 Language
                                            'ru' => [//Localization6 Language
                                                'ab' => []//Localization7 Language
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'locale' => 'ab',
                'expectedFallbackLocales' => ['ru', 'pl', 'en'],
            ],
        ];
    }

    /**
     * @dataProvider getAllFallbackLocalesDataProvider
     */
    public function testGetAllFallbackLocales(array $fallbackTree, array $expectedFallbackLocales)
    {
        $defaultStrategy = $this->createMock(TranslationStrategyInterface::class);

        $provider = new TranslationStrategyProvider([$defaultStrategy]);

        $testedStrategy = $this->createMock(TranslationStrategyInterface::class);
        $testedStrategy->expects($this->any())
            ->method('getLocaleFallbacks')
            ->willReturn($fallbackTree);

        $this->assertEquals($expectedFallbackLocales, $provider->getAllFallbackLocales($testedStrategy));
    }

    public function getAllFallbackLocalesDataProvider(): array
    {
        return [
            'simple tree' => [
                'fallbackTree' => [
                    'en' => [],
                ],
                'expectedFallbackLocales' => ['en'],
            ],
            'complex tree' => [
                'fallbackTree' => [
                    'en' => [
                        'en_US' => [
                            'en_CA' => [],
                            'en_MX' => [],
                        ],
                        'en_GB' => [],
                    ],
                    'ru' => [
                        'ru_RU' => [],
                        'ru_UA' => [],
                    ],
                ],
                'expectedFallbackLocales' => ['en', 'ru', 'en_US', 'en_GB', 'en_CA', 'en_MX', 'ru_RU', 'ru_UA'],
            ],
            'duplicated locales' => [
                'fallbackTree' => [
                    'en' => [
                        'en' => [],
                    ],
                ],
                'expectedFallbackLocales' => ['en'],
            ],
        ];
    }
}
