<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;
use Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationStrategyProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $defaultStrategy;

    /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $customStrategy;

    /** @var TranslationStrategyProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->defaultStrategy = $this->getStrategy('default');
        $this->customStrategy = $this->getStrategy('custom');

        $this->provider = new TranslationStrategyProvider();
        $this->provider->addStrategy($this->defaultStrategy);
        $this->provider->addStrategy($this->customStrategy);
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
     * @param array $fallbackTree
     * @param string $locale
     * @param array $expectedFallbackLocales
     *
     * @param $fallbackStrategyName
     *
     * @dataProvider getFallbackLocalesDataProvider
     */
    public function testGetFallbackLocales(
        array $fallbackTree,
        $locale,
        array $expectedFallbackLocales,
        $fallbackStrategyName
    ) {
        /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject $defaultStrategy */
        $defaultStrategy = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new TranslationStrategyProvider($defaultStrategy);

        /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject $defaultStrategy */
        $testedStrategy = $this->createMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $testedStrategy->expects($this->any())
            ->method('getLocaleFallbacks')
            ->willReturn($fallbackTree);

        if ($fallbackStrategyName) {
            $testedStrategy->expects($this->atLeastOnce())->method('getName')->willReturn($fallbackStrategyName);
        } else {
            $testedStrategy->expects($this->never())->method('getName');
        }

        $this->assertEquals($expectedFallbackLocales, $provider->getFallbackLocales($testedStrategy, $locale));
    }

    /**
     * @return array
     */
    public function getFallbackLocalesDataProvider()
    {
        return [
            'one node tree defined locale' => [
                'fallbackTree' => [
                    'en' => [],
                ],
                'locale' => 'en',
                'expectedFallbackLocales' => [],
                'fallbackStrategyName' => DefaultTranslationStrategy::NAME,
            ],
            'one node tree undefined locale' => [
                'fallbackTree' => [
                    'en' => [],
                ],
                'locale' => 'ru',
                'expectedFallbackLocales' => [Translator::DEFAULT_LOCALE],
                'fallbackStrategyName' => null,
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
                'fallbackStrategyName' => DefaultTranslationStrategy::NAME,
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
                'fallbackStrategyName' => DefaultTranslationStrategy::NAME,
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
                'fallbackStrategyName' => DefaultTranslationStrategy::NAME,
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
                'fallbackStrategyName' => LocalizationFallbackStrategy::NAME,
            ],
        ];
    }

    /**
     * @param array $fallbackTree
     * @param array $expectedFallbackLocales
     *
     * @dataProvider getAllFallbackLocalesDataProvider
     */
    public function testGetAllFallbackLocales(array $fallbackTree, array $expectedFallbackLocales)
    {
        /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject $defaultStrategy */
        $defaultStrategy = $this->createMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');

        $provider = new TranslationStrategyProvider($defaultStrategy);

        /** @var TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject $defaultStrategy */
        $testedStrategy = $this->createMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $testedStrategy->expects($this->any())
            ->method('getLocaleFallbacks')
            ->willReturn($fallbackTree);

        $this->assertEquals($expectedFallbackLocales, $provider->getAllFallbackLocales($testedStrategy));
    }

    /**
     * @return array
     */
    public function getAllFallbackLocalesDataProvider()
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

    /**
     * @param string $name
     *
     * @return TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStrategy($name)
    {
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategy->expects($this->any())->method('isApplicable')->willReturn(true);
        $strategy->expects($this->any())->method('getName')->willReturn($name);

        return $strategy;
    }
}
