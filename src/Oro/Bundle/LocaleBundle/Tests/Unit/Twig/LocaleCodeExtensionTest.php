<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\LocaleCodeFormatter;
use Oro\Bundle\LocaleBundle\Twig\LocaleCodeExtension;

class LocaleCodeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**  @var LocaleCodeExtension */
     protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleCodeFormatter */
    protected $formatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\LocaleCodeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new LocaleCodeExtension($this->formatter);
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('oro_locale_code_title', $filters[0]->getName());
    }

    public function testGetName()
    {
        $this->assertEquals(LocaleCodeExtension::NAME, $this->extension->getName());
    }

    public function testFormatProductType()
    {
        $this->formatter->expects($this->once())
            ->method('formatLocaleCode')
            ->with('en_CA')
        ;

        $this->extension->getLocaleTitleByCode('en_CA');
    }
}
