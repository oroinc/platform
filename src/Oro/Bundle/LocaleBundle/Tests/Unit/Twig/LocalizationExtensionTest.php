<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Twig\LocalizationExtension;

class LocalizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**  @var LocalizationExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LanguageCodeFormatter */
    protected $languageCodeFormatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormattingCodeFormatter */
    protected $formattingCodeFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->languageCodeFormatter = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formattingCodeFormatter = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new LocalizationExtension($this->languageCodeFormatter, $this->formattingCodeFormatter);
    }

    public function tearDown()
    {
        unset($this->languageCodeFormatter, $this->formattingCodeFormatter, $this->extension);
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('oro_language_code_title', $filters[0]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('oro_formatting_code_title', $filters[1]->getName());
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationExtension::NAME, $this->extension->getName());
    }

    public function testGetFormattingTitleByCode()
    {
        $this->formattingCodeFormatter->expects($this->once())
            ->method('format')
            ->with('en_CA');
        $this->extension->getFormattingTitleByCode('en_CA');
    }

    public function testGetLanguageTitleByCode()
    {
        $this->languageCodeFormatter->expects($this->once())
            ->method('format')
            ->with('en');

        $this->extension->getLanguageTitleByCode('en');
    }
}
