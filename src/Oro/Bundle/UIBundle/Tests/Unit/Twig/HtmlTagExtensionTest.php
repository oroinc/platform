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
        $filters = $this->extension->getFilters();

        $this->assertTrue(is_array($filters));
        $this->assertEquals(3, sizeof($filters));

        $filter = $filters[0];
        $this->assertInstanceOf('\Twig_SimpleFilter', $filter);
        $this->assertEquals($filter->getName(), 'oro_tag_filter');
        $callable = $filter->getCallable();
        $this->assertTrue(is_array($callable));
        $this->assertEquals(2, sizeof($callable));
        $this->assertEquals($callable[0], $this->extension);
        $this->assertEquals($callable[1], 'tagFilter');
    }
}
