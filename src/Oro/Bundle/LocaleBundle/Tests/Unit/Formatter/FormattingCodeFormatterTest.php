<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;

class FormattingCodeFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var LanguageCodeFormatter */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    protected $configManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        IntlTestHelper::requireIntl($this);

        $this->translator   = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->formatter = new FormattingCodeFormatter($this->translator, $this->configManager);
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

        $this->configManager->expects($value ? $this->once() : $this->never())
            ->method('get')
            ->with(LanguageCodeFormatter::CONFIG_KEY_DEFAULT_LANGUAGE)
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
