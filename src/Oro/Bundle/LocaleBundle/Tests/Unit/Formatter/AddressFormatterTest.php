<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;

class AddressFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|LocaleSettings */
    private $localeSettings;

    /** @var \PHPUnit\Framework\MockObject\MockObject|NameFormatter */
    private $nameFormatter;

    /** @var AddressFormatter */
    private $addressFormatter;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->nameFormatter = $this->createMock(NameFormatter::class);

        $this->addressFormatter = new AddressFormatter(
            $this->localeSettings,
            $this->nameFormatter,
            PropertyAccess::createPropertyAccessor()
        );
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testFormat(
        string $format,
        ?string $regionCode,
        string $expected,
        bool $formatByCountry = false,
        string $street2 = 'apartment 10',
        string $separator = "\n"
    ) {
        $address = new AddressStub($street2);
        $address->setRegionCode($regionCode);
        $locale = 'en';
        $country = 'CA';
        $addressFormats = [
            $country => [
                LocaleSettings::ADDRESS_FORMAT_KEY => $format
            ],
        ];

        $this->localeSettings->expects($this->any())
            ->method('getAddressFormats')
            ->willReturn($addressFormats);
        $this->localeSettings->expects($this->once())
            ->method('isFormatAddressByAddressCountry')
            ->willReturn($formatByCountry);
        $this->localeSettings->expects($this->any())
            ->method('getCountry')
            ->willReturn($country);
        if ($formatByCountry) {
            $this->localeSettings->expects($this->once())
                ->method('getLocaleByCountry')
                ->with($address->getCountryIso2())
                ->willReturn($locale);
        } else {
            $this->localeSettings->expects($this->once())
                ->method('getLocaleByCountry')
                ->with($country)
                ->willReturn($locale);
        }

        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->with($address, $locale)
            ->willReturn('Formatted User NAME');

        $this->assertEquals($expected, $this->addressFormatter->format($address, null, $separator));
    }

    public function formatDataProvider(): array
    {
        return [
            'simple street' => [
                '%name%\n%organization%\n%street%\n%CITY% %REGION_CODE% %COUNTRY% %postal_code%',
                'NY',
                "Formatted User NAME\nCompany Ltd.\n1 Tests str. apartment 10\nNEW YORK NY UNITED STATES 12345"
            ],
            'complex street' => [
                '%name%\n%organization%\n%street1%\n%street2%\n%CITY% %REGION_CODE% %COUNTRY% %postal_code%',
                'NY',
                "Formatted User NAME\nCompany Ltd.\n1 Tests str.\napartment 10\nNEW YORK NY UNITED STATES 12345"
            ],
            'unknown field' => [
                '%unknown_data_one% %name%\n'
                . '%organization%\n%street%\n%CITY% %REGION_CODE% %COUNTRY% %postal_code% %unknown_data_two%',
                'NY',
                "Formatted User NAME\nCompany Ltd.\n1 Tests str. apartment 10\nNEW YORK NY UNITED STATES 12345"
            ],
            'multi spaces' => [
                '%unknown_data_one% %name% %unknown_data_one%\n'
                . '%organization%\n%street%\n'
                . '%CITY% %unknown_data_one% %REGION_CODE% %COUNTRY% %postal_code% %unknown_data_two%',
                'NY',
                "Formatted User NAME\nCompany Ltd.\n1 Tests str. apartment 10\nNEW YORK NY UNITED STATES 12345"
            ],
            'address country format' => [
                '%name%\n%organization%\n%street%\n%CITY% %REGION_CODE% %COUNTRY% %postal_code%',
                'NY',
                "Formatted User NAME\nCompany Ltd.\n1 Tests str. apartment 10\nNEW YORK NY UNITED STATES 12345",
                true
            ],
            'unknown region code' => [
                '%name%\n%organization%\n%street%\n%CITY% %region_code% %COUNTRY% %postal_code%',
                null,
                "Formatted User NAME\nCompany Ltd.\n1 Tests str. apartment 10\nNEW YORK New York UNITED STATES 12345",
                true
            ],
            'region name' => [
                '%name%\n%organization%\n%street%\n%CITY% %region% %COUNTRY% %postal_code%',
                null,
                "Formatted User NAME\nCompany Ltd.\n1 Tests str. apartment 10\nNEW YORK New York UNITED STATES 12345",
                true
            ],
            'empty field with custom delimiter' => [
                '%name%\n%organization%\n%street%\n%CITY% %region% %COUNTRY% %postal_code% %name_suffix%',
                'NY',
                'Formatted User NAME, Company Ltd., 1 Tests str., NEW YORK New York UNITED STATES 12345',
                true,
                '',
                ', ',
            ],
        ];
    }

    /**
     * @dataProvider getAddressPartsDataProvider
     */
    public function testGetAddressParts(
        string $format,
        ?string $regionCode,
        array $expected,
        bool $formatByCountry = false,
        string $street2 = 'apartment 10'
    ): void {
        $address = new AddressStub($street2);
        $address->setRegionCode($regionCode);
        $locale = 'en';
        $country = 'CA';

        $this->localeSettings->expects($this->once())
            ->method('isFormatAddressByAddressCountry')
            ->willReturn($formatByCountry);

        $this->localeSettings->expects($this->any())
            ->method('getCountry')
            ->willReturn($country);

        if ($formatByCountry) {
            $this->localeSettings->expects($this->once())
                ->method('getLocaleByCountry')
                ->with($address->getCountryIso2())
                ->willReturn($locale);
        } else {
            $this->localeSettings->expects($this->once())
                ->method('getLocaleByCountry')
                ->with($country)
                ->willReturn($locale);
        }

        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->with($address, $locale)
            ->willReturn('Formatted User NAME');

        $this->assertEquals($expected, $this->addressFormatter->getAddressParts($address, $format));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getAddressPartsDataProvider(): array
    {
        return [
            'simple street' => [
                'format' => '%name%\n%organization%\n%street%\n%CITY% %REGION_CODE% %COUNTRY% %postal_code%',
                'regionCode' => 'NY',
                'expected' => [
                    '%name%' => 'Formatted User NAME',
                    '%organization%' => 'Company Ltd.',
                    '%street%' => '1 Tests str. apartment 10',
                    '%CITY%' => 'NEW YORK',
                    '%REGION_CODE%' => 'NY',
                    '%COUNTRY%' => 'UNITED STATES',
                    '%postal_code%' => '12345',
                ],
            ],
            'complex street' => [
                'format' => '%name%\n%organization%\n%street1%\n%street2%\n%CITY% %REGION_CODE% %COUNTRY%' .
                    '%postal_code%',
                'regionCode' => 'NY',
                'expected' => [
                    '%name%' => 'Formatted User NAME',
                    '%organization%' => 'Company Ltd.',
                    '%street1%' => '1 Tests str.',
                    '%street2%' => 'apartment 10',
                    '%CITY%' => 'NEW YORK',
                    '%REGION_CODE%' => 'NY',
                    '%COUNTRY%' => 'UNITED STATES',
                    '%postal_code%' => '12345',
                ],
            ],
            'unknown field' => [
                'format' => '%unknown_data_one% %name%\n%organization%\n%street%\n%CITY% %REGION_CODE% %COUNTRY% ' .
                    '%postal_code% %unknown_data_two%',
                'regionCode' => 'NY',
                'expected' => [
                    '%unknown_data_one%' => '',
                    '%name%' => 'Formatted User NAME',
                    '%organization%' => 'Company Ltd.',
                    '%street%' => '1 Tests str. apartment 10',
                    '%CITY%' => 'NEW YORK',
                    '%REGION_CODE%' => 'NY',
                    '%COUNTRY%' => 'UNITED STATES',
                    '%postal_code%' => '12345',
                    '%unknown_data_two%' => '',
                ],
            ],
            'multi spaces' => [
                'format' => '%unknown_data_one% %name% %unknown_data_one%\n%organization%\n%street%\n%CITY% ' .
                    '%unknown_data_one% %REGION_CODE% %COUNTRY% %postal_code% %unknown_data_two%',
                'regionCode' => 'NY',
                'expected' => [
                    '%unknown_data_one%' => '',
                    '%name%' => 'Formatted User NAME',
                    '%organization%' => 'Company Ltd.',
                    '%street%' => '1 Tests str. apartment 10',
                    '%CITY%' => 'NEW YORK',
                    '%REGION_CODE%' => 'NY',
                    '%COUNTRY%' => 'UNITED STATES',
                    '%postal_code%' => '12345',
                    '%unknown_data_two%' => '',
                ],
            ],
            'address country format' => [
                'format' => '%name%\n%organization%\n%street%\n%CITY% %REGION_CODE% %COUNTRY% %postal_code%',
                'regionCode' => 'NY',
                'expected' => [
                    '%name%' => 'Formatted User NAME',
                    '%organization%' => 'Company Ltd.',
                    '%street%' => '1 Tests str. apartment 10',
                    '%CITY%' => 'NEW YORK',
                    '%REGION_CODE%' => 'NY',
                    '%COUNTRY%' => 'UNITED STATES',
                    '%postal_code%' => '12345',
                ],
                'formatByCountry' => true,
            ],
            'unknown region code' => [
                'format' => '%name%\n%organization%\n%street%\n%CITY% %region_code% %COUNTRY% %postal_code%',
                'regionCode' => null,
                'expected' => [
                    '%name%' => 'Formatted User NAME',
                    '%organization%' => 'Company Ltd.',
                    '%street%' => '1 Tests str. apartment 10',
                    '%CITY%' => 'NEW YORK',
                    '%region_code%' => 'New York',
                    '%COUNTRY%' => 'UNITED STATES',
                    '%postal_code%' => '12345',
                ],
                'formatByCountry' => true,
            ],
            'region name' => [
                'format' => '%name%\n%organization%\n%street%\n%CITY% %region% %COUNTRY% %postal_code%',
                'regionCode' => null,
                'expected' => [
                    '%name%' => 'Formatted User NAME',
                    '%organization%' => 'Company Ltd.',
                    '%street%' => '1 Tests str. apartment 10',
                    '%CITY%' => 'NEW YORK',
                    '%region%' => 'New York',
                    '%COUNTRY%' => 'UNITED STATES',
                    '%postal_code%' => '12345',
                ],
                'formatByCountry' => true,
            ],
        ];
    }

    public function testGetAddressFormatFails()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get address format for "CA"');

        $this->localeSettings->expects($this->once())
            ->method('getCountry')
            ->willReturn(LocaleConfiguration::DEFAULT_COUNTRY);

        $this->addressFormatter->getAddressFormat('CA');
    }

    /**
     * @dataProvider getAddressFormatDataProvider
     */
    public function testGetAddressFormat(
        array $addressFormats,
        $localeOrRegion,
        string $expectedFormat,
        string $defaultCountry = null
    ) {
        $this->localeSettings->expects($this->once())
            ->method('getAddressFormats')
            ->willReturn($addressFormats);

        if (!$localeOrRegion) {
            $this->localeSettings->expects($this->once())
                ->method('getLocale')
                ->willReturn('en_US');
        }

        if ($defaultCountry) {
            $this->localeSettings->expects($this->once())
                ->method('getCountry')
                ->willReturn($defaultCountry);
        }

        $this->assertEquals($expectedFormat, $this->addressFormatter->getAddressFormat($localeOrRegion));
    }

    public function getAddressFormatDataProvider(): array
    {
        return [
            'direct' => [
                'addressFormats' => [
                    'US' => [LocaleSettings::ADDRESS_FORMAT_KEY => '%address_format%']
                ],
                'localeOrRegion' => 'US',
                'expectedFormat' => '%address_format%'
            ],
            'parse_country' => [
                'addressFormats' => [
                    'CA' => [LocaleSettings::ADDRESS_FORMAT_KEY => '%address_format%']
                ],
                'localeOrRegion' => 'fr_CA',
                'expectedFormat' => '%address_format%'
            ],
            'empty_locale_or_region' => [
                'addressFormats' => [
                    'RU' => [LocaleSettings::ADDRESS_FORMAT_KEY => '%address_format%']
                ],
                'localeOrRegion' => false,
                'expectedFormat' => '%address_format%',
                'defaultCountry' => 'RU'
            ],
            'default_system_country' => [
                'addressFormats' => [
                    'RU' => [LocaleSettings::ADDRESS_FORMAT_KEY => '%address_format%']
                ],
                'localeOrRegion' => 'fr_CA',
                'expectedFormat' => '%address_format%',
                'defaultCountry' => 'RU'
            ],
            'default_fallback' => [
                'addressFormats' => [
                    LocaleConfiguration::DEFAULT_COUNTRY => [
                        LocaleSettings::ADDRESS_FORMAT_KEY => '%address_format%'
                    ]
                ],
                'localeOrRegion' => 'fr_CA',
                'expectedFormat' => '%address_format%'
            ],
        ];
    }
}
