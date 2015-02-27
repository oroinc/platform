<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;

class HtmlTagExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HtmlTagExtension
     */
    protected $extension;

    /**
     * @var HtmlTagProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $htmlTagProvider;

    protected function setUp()
    {
        $this->htmlTagProvider = $this->getMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');

        $this->extension = new HtmlTagExtension($this->htmlTagProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_ui.html_tag', $this->extension->getName());
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertTrue(is_array($filters));
        $this->assertEquals(1, sizeof($filters));

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
