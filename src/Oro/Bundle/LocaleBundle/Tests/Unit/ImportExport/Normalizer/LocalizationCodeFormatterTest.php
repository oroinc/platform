<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;

class LocalizationCodeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationCodeFormatter */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new LocalizationCodeFormatter();
    }

    /**
     * @param mixed $localization
     * @param string $expected
     *
     * @dataProvider nameDataProvider
     */
    public function testFormatName($localization, $expected)
    {
        $this->assertEquals($expected, $this->formatter->formatName($localization));
    }

    public function nameDataProvider(): array
    {
        return [
            [null, 'default'],
            ['', 'default'],
            [false, 'default'],
            ['English', 'English'],
            [new Localization(), 'default'],
            [(new Localization())->setName('English'), 'English'],
        ];
    }

    /**
     * @param mixed $localization
     * @param string $expected
     *
     * @dataProvider keyDataProvider
     */
    public function testFormatKey($localization, $expected)
    {
        $this->assertEquals($expected, $this->formatter->formatKey($localization));
    }

    public function keyDataProvider(): array
    {
        return [
            [null, null],
            ['', null],
            [false, null],
            ['English', 'English'],
            [new Localization(), null],
            [(new Localization())->setName('English'), 'English'],
        ];
    }
}
