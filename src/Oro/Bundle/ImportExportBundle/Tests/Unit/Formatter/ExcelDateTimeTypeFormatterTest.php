<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Formatter\ExcelDateTimeTypeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Translation\Translator;

class ExcelDateTimeTypeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExcelDateTimeTypeFormatter
     */
    protected $formatter;

    public function setUp()
    {
        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator      = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new ExcelDateTimeTypeFormatter($localeSettings, $translator);
    }

    /**
     * @dataProvider getPatternProvider
     *
     * @param int    $timeType
     * @param string $locale
     * @param string $result
     */
    public function testGetPattern($timeType, $locale, $result)
    {
        $this->assertEquals($result, $this->formatter->getPattern(1, $timeType, $locale));
    }

    /**
     * @dataProvider getPatternWithValueProvider
     *
     * @param int    $timeType
     * @param string $locale
     * @param        $value
     * @param string $result
     */
    public function testGetPatternWithValue($timeType, $locale, $value, $result)
    {
        $this->assertEquals($result, $this->formatter->getPattern(1, $timeType, $locale, $value));
    }

    /**
     * @return array
     */
    public function getPatternProvider()
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

    /**
     * @return array
     */
    public function getPatternWithValueProvider()
    {
        return [
            'with seconds'    => [null, 'en', '20.01.1999 12:00:00', 'MM/dd/y HH:mm:ss'],
            'without seconds' => [null, 'en', '20.01.1999 12:00', 'MM/dd/y HH:mm'],
        ];
    }
}
