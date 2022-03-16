<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Formatter\ExcelDateTimeTypeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExcelDateTimeTypeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExcelDateTimeTypeFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $localeSettings = $this->createMock(LocaleSettings::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new ExcelDateTimeTypeFormatter($localeSettings, $translator);
    }

    /**
     * @dataProvider getPatternProvider
     */
    public function testGetPattern(?int $timeType, string $locale, string $result)
    {
        $this->assertEquals($result, $this->formatter->getPattern(1, $timeType, $locale));
    }

    /**
     * @dataProvider getPatternWithValueProvider
     */
    public function testGetPatternWithValue(?int $timeType, string $locale, string $value, string $result)
    {
        $this->assertEquals($result, $this->formatter->getPattern(1, $timeType, $locale, $value));
    }

    public function getPatternProvider(): array
    {
        return [
            'locale "de" with time'       => [null, 'de', 'dd.MM.y HH:mm:ss'],
            'locale "de" without time'    => [\IntlDateFormatter::NONE, 'de', 'dd.MM.y'],
            'locale "fr" with time'       => [null, 'fr', 'dd/MM/y HH:mm:ss'],
            'locale "fr" without time'    => [\IntlDateFormatter::NONE, 'fr', 'dd/MM/y'],
            'locale "ru" with time'       => [null, 'ru', 'dd.MM.y HH:mm:ss'],
            'locale "ru" without time'    => [\IntlDateFormatter::NONE, 'ru', 'dd.MM.y'],
            'locale "en" with time'       => [null, 'en', 'MM/dd/y HH:mm:ss'],
            'locale "en" without time'    => [\IntlDateFormatter::NONE, 'en', 'MM/dd/y'],
            'locale "en_US" with time'    => [null, 'en_US', 'MM/dd/y HH:mm:ss'],
            'locale "en_US" without time' => [\IntlDateFormatter::NONE, 'en_US', 'MM/dd/y']
        ];
    }

    public function getPatternWithValueProvider(): array
    {
        return [
            'with seconds'    => [null, 'en', '20.01.1999 12:00:00', 'MM/dd/y HH:mm:ss'],
            'without seconds' => [null, 'en', '20.01.1999 12:00', 'MM/dd/y HH:mm'],
        ];
    }
}
