<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationStrategyProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $defaultStrategy;

    /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject */
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

    public function testSelectStrategy()
    {
        $this->assertSame($this->defaultStrategy, $this->provider->getStrategy());

        $this->provider->selectStrategy('custom');

        $this->assertSame($this->customStrategy, $this->provider->getStrategy());
    }

    public function testResetStrategy()
    {
        $this->provider->selectStrategy('custom');

        $this->assertSame($this->customStrategy, $this->provider->getStrategy());

        $this->provider->resetStrategy();

        $this->assertSame($this->defaultStrategy, $this->provider->getStrategy());
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
     * @dataProvider getFallbackLocalesDataProvider
     */
    public function testGetFallbackLocales(array $fallbackTree, $locale, array $expectedFallbackLocales)
    {
        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $defaultStrategy */
        $defaultStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');

        $provider = new TranslationStrategyProvider($defaultStrategy);

        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $defaultStrategy */
        $testedStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $testedStrategy->expects($this->any())
            ->method('getLocaleFallbacks')
            ->willReturn($fallbackTree);

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
        ];
    }

    /**
     * @param array $fallbackTree
     * @param array $expectedFallbackLocales
     * @dataProvider getAllFallbackLocalesDataProvider
     */
    public function testGetAllFallbackLocales(array $fallbackTree, array $expectedFallbackLocales)
    {
        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $defaultStrategy */
        $defaultStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');

        $provider = new TranslationStrategyProvider($defaultStrategy);

        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $defaultStrategy */
        $testedStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
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
     * @return TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStrategy($name)
    {
        $strategy = $this->getMock(TranslationStrategyInterface::class);
        $strategy->expects($this->any())->method('isApplicable')->willReturn(true);
        $strategy->expects($this->any())->method('getName')->willReturn($name);

        return $strategy;
    }
}
