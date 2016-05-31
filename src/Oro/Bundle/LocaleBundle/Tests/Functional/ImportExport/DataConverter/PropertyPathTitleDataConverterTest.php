<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter;

/**
 * @covers \Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter
 * @dbIsolation
 */
class PropertyPathTitleDataConverterTest extends WebTestCase
{
    /**
     * @var PropertyPathTitleDataConverter
     */
    protected $converter;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            ['OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadLocaleData']
        );

        $container = $this->getContainer();

        $this->converter = new PropertyPathTitleDataConverter(
            $container->get('oro_importexport.field.field_helper'),
            $container->get('oro_importexport.data_converter.relation_calculator')
        );
        $this->converter->setDispatcher($container->get('event_dispatcher'));
    }

    /**
     * @param array $data
     * @param array $expected
     *
     * @dataProvider importDataProvider
     */
    public function testConvertToImportFormat(array $data, array $expected)
    {
        $fallbackClass = $this->getContainer()
            ->getParameter('oro_locale.entity.localized_fallback_value.class');

        $this->converter->setEntityName($fallbackClass);

        $this->assertEquals($expected, $this->converter->convertToImportFormat($data));
    }

    /**
     * @return array
     */
    public function importDataProvider()
    {
        return [
            [
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'locale.code' => 'en',
                ],
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'locale' => ['code' => 'en'],
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
        $fallbackClass = $this->getContainer()
            ->getParameter('oro_locale.entity.localized_fallback_value.class');

        $this->converter->setEntityName($fallbackClass);

        $this->assertEquals($expected, $this->converter->convertToExportFormat($data));
    }

    /**
     * @return array
     */
    public function exportDataProvider()
    {
        return [
            [
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'locale' => ['code' => 'en'],
                ],
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'locale.code' => 'en',
                ],
            ],
        ];
    }
}
