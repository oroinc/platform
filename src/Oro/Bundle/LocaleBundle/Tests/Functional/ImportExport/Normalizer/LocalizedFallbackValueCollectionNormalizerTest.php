<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @covers \Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer
 * @dbIsolation
 */
class LocalizedFallbackValueCollectionNormalizerTest extends WebTestCase
{
    use EntityTrait;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * @param array $actualData
     * @param array $expectedData
     *
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(array $actualData, array $expectedData = [])
    {
        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('oro_locale.entity.localization.class')
        );

        $this->assertEquals(
            $expectedData,
            $normalizer->normalize(new ArrayCollection($actualData))
        );
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        return [
            'without localization' => [
                [
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'string' => 'value', 'localization' => null]
                    ),
                ],
                ['default' => ['fallback' => 'system', 'string' => 'value', 'text' => null]],
            ],
            'localization without languageCode' => [
                [
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'text' => 'value', 'localization' => new Localization()]
                    ),
                ],
                ['default' => ['fallback' => 'system', 'string' => null, 'text' => 'value']],
            ],
            'localization with languageCode' => [
                [
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => (new Localization())->setLanguageCode('en')
                        ]
                    ),
                ],
                ['en' => ['fallback' => 'system', 'string' => null, 'text' => 'value']],
            ],
            'mixed' => [
                [
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => (new Localization())->setLanguageCode('en')
                        ]
                    ),
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        [
                            'fallback' => 'system',
                            'string' => 'value',
                            'localization' => (new Localization())->setLanguageCode('en_CA')
                        ]
                    ),
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'text' => 'value', 'localization' => new Localization()]
                    ),
                ],
                [
                    'en' => ['fallback' => 'system', 'string' => null, 'text' => 'value'],
                    'en_CA' => ['fallback' => 'system', 'string' => 'value', 'text' => null],
                    'default' => ['fallback' => 'system', 'string' => null, 'text' => 'value'],
                ],
            ],
        ];
    }

    /**
     * @param mixed $actualData
     * @param string $class
     * @param ArrayCollection $expectedData
     *
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalizer($actualData, $class, ArrayCollection $expectedData)
    {
        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('oro_locale.entity.localization.class')
        );

        $this->assertEquals($expectedData, $normalizer->denormalize($actualData, $class));
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
    {
        return [
            'not and array' => [
                'value',
                'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                new ArrayCollection(),
            ],
            'wrong type' => [
                [],
                'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                new ArrayCollection(),
            ],
            'type' => [
                [],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                new ArrayCollection(),
            ],
            'without localization' => [
                ['default' => ['fallback' => 'system', 'string' => 'value', 'text' => null]],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                new ArrayCollection(
                    [
                        'default' => $this->getEntity(
                            'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            ['fallback' => 'system', 'string' => 'value']
                        ),
                    ]
                ),
            ],
            'localization with languageCode' => [
                ['en' => ['fallback' => 'system', 'string' => 'value']],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                new ArrayCollection(
                    [
                        'en' => $this->getEntity(
                            'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            [
                                'fallback' => 'system',
                                'string' => 'value',
                                'localization' => (new Localization())->setLanguageCode('en'),
                            ]
                        ),
                    ]
                ),
            ],
            'mixed' => [
                [
                    'default' => ['fallback' => 'system', 'string' => 'value', 'text' => null],
                    'en' => ['string' => 'value'],
                    'en_CA' => ['fallback' => 'parent_localization', 'text' => 'value'],
                ],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                new ArrayCollection(
                    [
                        'default' => $this->getEntity(
                            'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            ['fallback' => 'system', 'string' => 'value']
                        ),
                        'en' => $this->getEntity(
                            'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            ['string' => 'value', 'localization' => (new Localization())->setLanguageCode('en')]
                        ),
                        'en_CA' => $this->getEntity(
                            'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            [
                                'fallback' => 'parent_localization',
                                'text' => 'value',
                                'localization' => (new Localization())->setLanguageCode('en_CA'),
                            ]
                        ),
                    ]
                ),
            ],
        ];
    }

    /**
     * @param mixed $data
     * @param bool $expected
     * @param array $context
     *
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization($data, $expected, array $context = [])
    {
        if (!$this->getContainer()->hasParameter('orob2b_product.entity.product.class')) {
            $this->markTestSkipped('ProductBundle required');
        }

        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('oro_locale.entity.localization.class')
        );

        $this->assertEquals($expected, $normalizer->supportsNormalization($data, [], $context));

        // trigger caches
        $this->assertEquals($expected, $normalizer->supportsNormalization($data, [], $context));
    }

    /**
     * @return array
     */
    public function supportsNormalizationDataProvider()
    {
        return [
            'not a collection' => [[], false],
            'collection' => [new ArrayCollection(), false],
            'not existing collection field' => [
                new ArrayCollection(),
                false,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names1'],
            ],
            'not supported field' => [
                new ArrayCollection(),
                false,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'unitPrecisions'],
            ],
            'supported field' => [
                new ArrayCollection(),
                true,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names'],
            ],
        ];
    }

    /**
     * @param mixed $data
     * @param string $class
     * @param bool $expected
     * @param array $context
     *
     * @dataProvider supportsdeDenormalizationDataProvider
     */
    public function testSupportsDenormalization($data, $class, $expected, array $context = [])
    {
        if (!$this->getContainer()->hasParameter('orob2b_product.entity.product.class')) {
            $this->markTestSkipped('ProductBundle required');
        }

        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('oro_locale.entity.localization.class')
        );

        $this->assertEquals($expected, $normalizer->supportsDenormalization($data, $class, [], $context));

        // trigger caches
        $this->assertEquals($expected, $normalizer->supportsDenormalization($data, $class, [], $context));
    }

    /**
     * @return array
     */
    public function supportsdeDenormalizationDataProvider()
    {
        return [
            'not a collection' => [new ArrayCollection(), 'OroB2B\Bundle\ProductBundle\Entity\Product', false],
            'not existing collection field' => [
                new ArrayCollection(),
                'ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\Product>',
                false,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names1'],
            ],
            'not supported field' => [
                new ArrayCollection(),
                'ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\Product>',
                false,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'unitPrecisions'],
            ],
            'supported field' => [
                new ArrayCollection(),
                'ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\Product>',
                true,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names'],
            ],
            'namespace' => [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\Product>',
                true,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names'],
            ],
            'not supported class' => [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\ProductUnit>',
                true,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names'],
            ],
        ];
    }
}
