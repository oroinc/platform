<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocaleCodeFormatter;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocaleCodeFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocaleCodeFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = new LocaleCodeFormatter();
    }

    /**
     * @param mixed $locale
     * @param string $expected
     *
     * @dataProvider nameDataProvider
     */
    public function testFormatName($locale, $expected)
    {
        $this->assertEquals($expected, $this->formatter->formatName($locale));
    }

    /**
     * @return array
     */
    public function nameDataProvider()
    {
        return [
            [null, 'default'],
            ['', 'default'],
            [false, 'default'],
            ['en', 'en'],
            [new Locale(), 'default'],
            [(new Locale())->setCode('en'), 'en'],
        ];
    }

    /**
     * @param mixed $locale
     * @param string $expected
     *
     * @dataProvider keyDataProvider
     */
    public function testFormatKey($locale, $expected)
    {
        $this->assertEquals($expected, $this->formatter->formatKey($locale));
    }

    /**
     * @return array
     */
    public function keyDataProvider()
    {
        return [
            [null, null],
            ['', null],
            [false, null],
            ['en', 'en'],
            [new Locale(), null],
            [(new Locale())->setCode('en'), 'en'],
        ];
    }
}
