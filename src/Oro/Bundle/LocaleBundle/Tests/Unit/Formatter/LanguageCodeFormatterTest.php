<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\Translation\TranslatorInterface;

class LanguageCodeFormatterTest extends TestCase
{
    /** @var LanguageCodeFormatter */
    protected $formatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LocaleSettings */
    protected $localeSettings;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        IntlTestHelper::requireIntl($this);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->formatter = new LanguageCodeFormatter($this->translator, $this->localeSettings);
    }

    /**
     * @param string $value
     * @param string $expected
     *
     * @dataProvider formatLanguageCodeProvider
     */
    public function testFormatLanguageCode($value, $expected)
    {
        $this->translator->expects($value ? $this->never() : $this->once())
            ->method('trans')
            ->with('N/A')
            ->willReturn('N/A');

        $this->localeSettings->expects($value ? $this->once() : $this->never())
            ->method('getLanguage')
            ->willReturn('en');

        $this->assertSame($expected, $this->formatter->format($value));
    }

    /**
     * @return array
     */
    public function formatLanguageCodeProvider()
    {
        return [
            [
                'value' => 'en_CA',
                'expected' => 'Canadian English',
            ],
            [
                'value' => 'bad_Code',
                'expected' => 'bad_Code',
            ],
            [
                'value' => '',
                'expected' => 'N/A',
            ],
        ];
    }

    /**
     * @param string $value
     * @param string $expected
     *
     * @dataProvider formatLocaleCodeProvider
     */
    public function testFormatLocaleCode($value, $expected)
    {
        $this->translator->expects($value ? $this->never() : $this->once())
            ->method('trans')
            ->with('N/A')
            ->willReturn('N/A');

        $this->localeSettings->expects($value ? $this->once() : $this->never())
            ->method('getLanguage')
            ->willReturn('en');

        $this->assertSame($expected, $this->formatter->formatLocale($value));
    }

    /**
     * @return array
     */
    public function formatLocaleCodeProvider()
    {
        return [
            [
                'value' => 'en_CA',
                'expected' => 'English (Canada)',
            ],
            [
                'value' => 'bad_Code',
                'expected' => 'bad_Code',
            ],
            [
                'value' => '',
                'expected' => 'N/A',
            ],
        ];
    }
}
