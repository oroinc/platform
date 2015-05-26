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
        $this->htmlTagHelper = $this->getMock('Oro\Bundle\FormBundle\Provider\HtmlTagHelper');

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
        $this->assertEquals(2, sizeof($filters));

        $filter = $filters[0];
        $this->assertInstanceOf('\Twig_SimpleFilter', $filter);
        $this->assertEquals($filter->getName(), 'oro_tag_filter');
        $callable = $filter->getCallable();
        $this->assertTrue(is_array($callable));
        $this->assertEquals(2, sizeof($callable));
        $this->assertEquals($callable[0], $this->extension);
        $this->assertEquals($callable[1], 'tagFilter');
    }

    public function testHtmlPurify()
    {
        $testString = <<<STR
<html dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="GENERATOR" content="MSHTML 10.00.9200.17228">
<style id="owaParaStyle">P {
	MARGIN-BOTTOM: 0px; MARGIN-TOP: 0px
}
</style>
</head>
<body fPStyle="1" ocsi="0">
<div style="direction: ltr;font-family: Tahoma;color: #000000;font-size: 10pt;">no subject</div>
</body>
</html>

STR;
        $this->assertEquals(
            '<div style="font-family:Tahoma;color:#000000;font-size:10pt;">no subject</div>',
            trim($this->extension->htmlPurify($testString))
        );
    }
}
