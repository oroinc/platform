<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

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
            $this->getContainer()->getParameter('orob2b_website.entity.locale.class')
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
            'without locale' => [
                [
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'string' => 'value', 'locale' => null]
                    ),
                ],
                ['default' => ['fallback' => 'system', 'string' => 'value', 'text' => null]],
            ],
            'locale without code' => [
                [
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'text' => 'value', 'locale' => new Locale()]
                    ),
                ],
                ['default' => ['fallback' => 'system', 'string' => null, 'text' => 'value']],
            ],
            'locale with code' => [
                [
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'text' => 'value', 'locale' => (new Locale())->setCode('en')]
                    ),
                ],
                ['en' => ['fallback' => 'system', 'string' => null, 'text' => 'value']],
            ],
            'mixed' => [
                [
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'text' => 'value', 'locale' => (new Locale())->setCode('en')]
                    ),
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'string' => 'value', 'locale' => (new Locale())->setCode('en_CA')]
                    ),
                    $this->getEntity(
                        'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        ['fallback' => 'system', 'text' => 'value', 'locale' => new Locale()]
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
            $this->getContainer()->getParameter('orob2b_website.entity.locale.class')
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
            'without locale' => [
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
            'locale with code' => [
                ['en' => ['fallback' => 'system', 'string' => 'value']],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                new ArrayCollection(
                    [
                        'en' => $this->getEntity(
                            'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            ['fallback' => 'system', 'string' => 'value', 'locale' => (new Locale())->setCode('en')]
                        ),
                    ]
                ),
            ],
            'mixed' => [
                [
                    'default' => ['fallback' => 'system', 'string' => 'value', 'text' => null],
                    'en' => ['string' => 'value'],
                    'en_CA' => ['fallback' => 'parent_locale', 'text' => 'value'],
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
                            ['string' => 'value', 'locale' => (new Locale())->setCode('en')]
                        ),
                        'en_CA' => $this->getEntity(
                            'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            [
                                'fallback' => 'parent_locale',
                                'text' => 'value',
                                'locale' => (new Locale())->setCode('en_CA'),
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
            $this->getContainer()->getParameter('orob2b_website.entity.locale.class')
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
            $this->getContainer()->getParameter('orob2b_website.entity.locale.class')
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
