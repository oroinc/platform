<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;

class TranslationStrategyProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetStrategy()
    {
        /** @var TranslationStrategyInterface $defaultStrategy */
        $defaultStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');

        $provider = new TranslationStrategyProvider($defaultStrategy);
        $this->assertEquals($defaultStrategy, $provider->getStrategy());
    }

    public function testSetStrategy()
    {
        $defaultName = 'default';
        $customName = 'custom';

        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $defaultStrategy */
        $defaultStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $defaultStrategy->expects($this->any())
            ->method('getName')
            ->willReturn($defaultName);
        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $customStrategy */
        $customStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $customStrategy->expects($this->any())
            ->method('getName')
            ->willReturn($customName);

        $provider = new TranslationStrategyProvider($defaultStrategy);
        $this->assertEquals($defaultStrategy, $provider->getStrategy());
        $this->assertEquals($defaultName, $provider->getStrategy()->getName());
        $provider->setStrategy($customStrategy);
        $this->assertEquals($customStrategy, $provider->getStrategy());
        $this->assertEquals($customName, $provider->getStrategy()->getName());
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
                'expectedFallbackLocales' => [],
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
}
