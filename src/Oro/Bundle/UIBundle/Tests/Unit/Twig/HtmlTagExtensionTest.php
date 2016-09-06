<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;

class HtmlTagExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HtmlTagExtension
     */
    protected $extension;

    /**
     * @var HtmlTagHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $htmlTagHelper;

    protected function setUp()
    {
        $this->htmlTagHelper = $this->getMockBuilder('Oro\Bundle\UIBundle\Tools\HtmlTagHelper')
            ->disableOriginalConstructor()->getMock();

        $this->extension = new HtmlTagExtension($this->htmlTagHelper);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_ui.html_tag', $this->extension->getName());
    }

    public function testGetFilters()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFilter('oro_tag_filter', [$this->extension, 'tagFilter'], ['is_safe' => ['all']]),
                new \Twig_SimpleFilter('oro_html_purify', [$this->extension, 'htmlPurify']),
                new \Twig_SimpleFilter('oro_html_sanitize', [$this->extension, 'htmlSanitize'], ['is_safe' => ['html']])
            ],
            $this->extension->getFilters()
        );
    }
}
