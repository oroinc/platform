<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;

/**
 * @covers \Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter
 * @dbIsolation
 */
class LocalizedFallbackValueAwareDataConverterTest extends WebTestCase
{
    /**
     * @var LocalizedFallbackValueAwareDataConverter
     */
    protected $converter;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();

        $container = $this->getContainer();

        if (!$container->hasParameter('orob2b_product.entity.product.class')) {
            $this->markTestSkipped('ProductBundle is missing');
        }

        $this->loadFixtures(
            ['Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData']
        );

        $this->converter = new LocalizedFallbackValueAwareDataConverter(
            $container->get('oro_importexport.field.field_helper'),
            $container->get('oro_importexport.data_converter.relation_calculator')
        );
        $this->converter->setDispatcher($container->get('event_dispatcher'));
        $this->converter->setRegistry($container->get('doctrine'));
        $this->converter->setLocalizedFallbackValueClassName(
            $container->getParameter('oro_locale.entity.localized_fallback_value.class')
        );
        $this->converter->setLocalizationClassName(
            $container->getParameter('oro_locale.entity.localization.class')
        );
    }

    /**
     * @param array $data
     * @param array $expected
     *
     * @dataProvider importDataProvider
     */
    public function testConvertToImportFormat(array $data, array $expected)
    {
        $productClass = $this->getContainer()->getParameter('orob2b_product.entity.product.class');

        $this->converter->setEntityName($productClass);

        $this->assertEquals($expected, $this->converter->convertToImportFormat($data));
    }

    /**
     * @return array
     */
    public function importDataProvider()
    {
        return [
            'default localization' => [
                ['names.default.fallback' => 'system', 'names.default.value' => 'default value'],
                ['names' => ['default' => ['fallback' => 'system', 'string' => 'default value']]],
            ],
            'en localization' => [
                ['names.en.fallback' => 'system', 'names.en.value' => 'en value'],
                ['names' => ['en' => ['fallback' => 'system', 'string' => 'en value']]],
            ],
            'custom localizations' => [
                [
                    'names.en_US.fallback' => 'parent_localization',
                    'names.en_US.value' => '',
                    'names.en_CA.fallback' => '',
                    'names.en_CA.value' => 'en_CA value',
                ],
                [
                    'names' => [
                        'en_US' => ['fallback' => 'parent_localization'],
                        'en_CA' => ['string' => 'en_CA value'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $data
     * @param array $expected
     *
     * @dataProvider exportDataProvider
     */
    public function testConvertToExportFormat(array $data, array $expected)
    {
        $productClass = $this->getContainer()->getParameter('orob2b_product.entity.product.class');

        $this->converter->setEntityName($productClass);

        $this->assertEquals($expected, $this->converter->convertToExportFormat($data));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function exportDataProvider()
    {
        return [
            'default localization' => [
                ['names' => ['default' => ['fallback' => 'system', 'string' => 'default value']]],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => 'system',
                    'names.default.value' => 'default value',
                    'names.en.fallback' => '',
                    'names.en.value' => '',
                    'names.en_US.fallback' => '',
                    'names.en_US.value' => '',
                    'names.en_CA.fallback' => '',
                    'names.en_CA.value' => '',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.en.fallback' => '',
                    'descriptions.en.value' => '',
                    'descriptions.en_US.fallback' => '',
                    'descriptions.en_US.value' => '',
                    'descriptions.en_CA.fallback' => '',
                    'descriptions.en_CA.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.en.fallback' => '',
                    'shortDescriptions.en.value' => '',
                    'shortDescriptions.en_US.fallback' => '',
                    'shortDescriptions.en_US.value' => '',
                    'shortDescriptions.en_CA.fallback' => '',
                    'shortDescriptions.en_CA.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
            'en localization' => [
                ['names' => ['en' => ['fallback' => 'system', 'string' => 'en value']]],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => '',
                    'names.default.value' => '',
                    'names.en.fallback' => 'system',
                    'names.en.value' => 'en value',
                    'names.en_US.fallback' => '',
                    'names.en_US.value' => '',
                    'names.en_CA.fallback' => '',
                    'names.en_CA.value' => '',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.en.fallback' => '',
                    'descriptions.en.value' => '',
                    'descriptions.en_US.fallback' => '',
                    'descriptions.en_US.value' => '',
                    'descriptions.en_CA.fallback' => '',
                    'descriptions.en_CA.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.en.fallback' => '',
                    'shortDescriptions.en.value' => '',
                    'shortDescriptions.en_US.fallback' => '',
                    'shortDescriptions.en_US.value' => '',
                    'shortDescriptions.en_CA.fallback' => '',
                    'shortDescriptions.en_CA.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
            'custom localization' => [
                [
                    'names' => [
                        'en_US' => ['fallback' => 'parent_localization'],
                        'en_CA' => ['string' => 'en_CA value'],
                    ],
                ],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => '',
                    'names.default.value' => '',
                    'names.en.fallback' => '',
                    'names.en.value' => '',
                    'names.en_US.fallback' => 'parent_localization',
                    'names.en_US.value' => '',
                    'names.en_CA.fallback' => '',
                    'names.en_CA.value' => 'en_CA value',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.en.fallback' => '',
                    'descriptions.en.value' => '',
                    'descriptions.en_US.fallback' => '',
                    'descriptions.en_US.value' => '',
                    'descriptions.en_CA.fallback' => '',
                    'descriptions.en_CA.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.en.fallback' => '',
                    'shortDescriptions.en.value' => '',
                    'shortDescriptions.en_US.fallback' => '',
                    'shortDescriptions.en_US.value' => '',
                    'shortDescriptions.en_CA.fallback' => '',
                    'shortDescriptions.en_CA.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
        ];
    }
}
