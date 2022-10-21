<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class LanguageCodeFormatterTest extends TestCase
{
    /** @var LanguageCodeFormatter */
    private $formatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LocaleSettings */
    private $localeSettings;

    protected function setUp(): void
    {
        IntlTestHelper::requireIntl($this);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->formatter = new LanguageCodeFormatter($this->translator, $this->localeSettings);
    }

    /**
     * @dataProvider formatLanguageCodeProvider
     */
    public function testFormatLanguageCode(string $value, string $expected)
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

    public function formatLanguageCodeProvider(): array
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
            [
                'value' => 'en_plastimo',
                'expected' => 'English Plastimo',
            ],
            [
                'value' => 'en_CA_plastimo',
                'expected' => 'Canadian English Plastimo',
            ],
            [
                'value' => 'fr_plastimo_FR',
                'expected' => 'French Plastimo FR',
            ]
        ];
    }

    /**
     * @dataProvider formatLocaleCodeProvider
     */
    public function testFormatLocaleCode(string $value, string $expected)
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

    public function formatLocaleCodeProvider(): array
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
            [
                'value' => 'en_plastimo',
                'expected' => 'English Plastimo',
            ],
            [
                'value' => 'en_CA_plastimo',
                'expected' => 'English (Canada) Plastimo',
            ]
        ];
    }
}
