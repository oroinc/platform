<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Twig\LocalizationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class LocalizationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /**  @var LocalizationExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LanguageCodeFormatter */
    protected $languageCodeFormatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormattingCodeFormatter */
    protected $formattingCodeFormatter;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $localizationHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->languageCodeFormatter = $this->getMockBuilder(LanguageCodeFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formattingCodeFormatter = $this->getMockBuilder(FormattingCodeFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.language_code', $this->languageCodeFormatter)
            ->add('oro_locale.formatter.formatting_code', $this->formattingCodeFormatter)
            ->add('oro_locale.helper.localization', $this->localizationHelper)
            ->getContainer($this);

        $this->extension = new LocalizationExtension($container);
    }

    public function tearDown()
    {
        unset(
            $this->languageCodeFormatter,
            $this->formattingCodeFormatter,
            $this->localizationHelper,
            $this->extension
        );
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationExtension::NAME, $this->extension->getName());
    }

    public function testGetFormattingTitleByCode()
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

    public function testGetLanguageTitleByCode()
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
