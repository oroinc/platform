<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Twig\LocalizationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizationExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private LanguageCodeFormatter&MockObject $languageCodeFormatter;
    private FormattingCodeFormatter&MockObject $formattingCodeFormatter;
    private LocalizationHelper&MockObject $localizationHelper;
    private LocalizationExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->languageCodeFormatter = $this->createMock(LanguageCodeFormatter::class);
        $this->formattingCodeFormatter = $this->createMock(FormattingCodeFormatter::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $container = self::getContainerBuilder()
            ->add(LanguageCodeFormatter::class, $this->languageCodeFormatter)
            ->add(FormattingCodeFormatter::class, $this->formattingCodeFormatter)
            ->add(LocalizationHelper::class, $this->localizationHelper)
            ->getContainer($this);

        $this->extension = new LocalizationExtension($container);
    }

    public function testGetFormattingTitleByCode(): void
    {
        $expected = 'result';

        $this->formattingCodeFormatter->expects($this->once())
            ->method('format')
            ->with('en_CA')
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_formatting_code_title', ['en_CA'])
        );
    }

    public function testGetLanguageTitleByCode(): void
    {
        $expected = 'result';

        $this->languageCodeFormatter->expects($this->once())
            ->method('format')
            ->with('en')
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_language_code_title', ['en'])
        );
    }
}
