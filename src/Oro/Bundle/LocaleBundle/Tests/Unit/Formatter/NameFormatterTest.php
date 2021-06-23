<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\PersonAllNamePartsStub;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\PersonFullNameStub;

class NameFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var NameFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->formatter = new NameFormatter($this->localeSettings);
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testFormat(string $format, string $expected, object $person)
    {
        $this->localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturn(LocaleConfiguration::DEFAULT_LOCALE);
        $this->localeSettings->expects($this->once())
            ->method('getNameFormats')
            ->willReturn([LocaleConfiguration::DEFAULT_LOCALE => $format]);

        $this->assertEquals($expected, $this->formatter->format($person));
    }

    public function formatDataProvider(): array
    {
        return [
            'object implements all name interfaces'                                         => [
                '%last_name% %FIRST_NAME% %middle_name% %PREFIX% %suffix%',
                'ln FN mn NP ns',
                new PersonAllNamePartsStub()
            ],
            'object implements all name interfaces, has both prepend and append separators' => [
                '(%first_name% %last_name%) - %suffix%!',
                '(fn ln) - ns!',
                new PersonAllNamePartsStub()
            ],
            'object implements full name interface, has unknown placeholders'               => [
                '%unknown_data_one% %last_name% %FIRST_NAME% %middle_name% %PREFIX% %suffix% %unknown_data_two%',
                'ln FN mn NP ns',
                new PersonFullNameStub()
            ],
            'object implements all name interfaces, has unknown placeholders'               => [
                '%last_name% %unknown_data_one% %FIRST_NAME% %middle_name% %PREFIX% %suffix%',
                'ln FN mn NP ns',
                new PersonAllNamePartsStub()
            ],
            'object does not implement name interfaces'                                     => [
                '%last_name% %first_name% %middle_name% %prefix% %suffix%',
                '',
                new \stdClass()
            ],
        ];
    }

    public function testGetNameFormatFails()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get name format for "fr_CA"');

        $this->localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturn(LocaleConfiguration::DEFAULT_LOCALE);

        $this->formatter->getNameFormat('fr_CA');
    }

    /**
     * @dataProvider getNameFormatDataProvider
     */
    public function testGetNameFormat(
        array $nameFormats,
        $locale,
        string $expectedFormat,
        string $defaultLocale = null
    ) {
        $this->localeSettings->expects($this->once())
            ->method('getNameFormats')
            ->willReturn($nameFormats);

        if (null !== $defaultLocale) {
            $this->localeSettings->expects($this->once())
                ->method('getLocale')
                ->willReturn($defaultLocale);
        } else {
            $this->localeSettings->expects($this->never())
                ->method('getLocale');
        }

        $this->assertEquals($expectedFormat, $this->formatter->getNameFormat($locale));
    }

    public function getNameFormatDataProvider(): array
    {
        return [
            'direct'                => [
                'nameFormats'    => [
                    'en_US' => '%name_format%'
                ],
                'locale'         => 'en_US',
                'expectedFormat' => '%name_format%'
            ],
            'parse_language'        => [
                'nameFormats'    => [
                    'fr' => '%name_format%'
                ],
                'locale'         => 'fr_CA',
                'expectedFormat' => '%name_format%'
            ],
            'empty_locale'          => [
                'nameFormats'    => [
                    'en_US' => '%name_format%'
                ],
                'locale'         => false,
                'expectedFormat' => '%name_format%',
                'defaultLocale'  => 'en_US'
            ],
            'default_system_locale' => [
                'nameFormats'    => [
                    'en_US' => '%name_format%'
                ],
                'locale'         => 'fr_CA',
                'expectedFormat' => '%name_format%',
                'defaultLocale'  => 'en_US'
            ],
            'default_fallback'      => [
                'nameFormats'    => [
                    LocaleConfiguration::DEFAULT_LOCALE => '%name_format%'
                ],
                'locale'         => 'fr_CA',
                'expectedFormat' => '%name_format%',
                'defaultLocale'  => ''
            ],
        ];
    }
}
