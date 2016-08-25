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
                new \Twig_SimpleFilter('oro_simple_html_purify', [$this->extension, 'simpleHtmlPurify']),
                new \Twig_SimpleFilter('oro_html_sanitize', [$this->extension, 'htmlSanitize'], ['is_safe' => ['html']])
            ],
            $this->extension->getFilters()
        );
    }

    /**
     * @dataProvider simpleHtmlPurifyDataProvider
     */
    public function testSimpleHtmlPurify($html, $purifiedHtml)
    {
        $this->assertEquals($purifiedHtml, $this->extension->simpleHtmlPurify($html));
    }

    public function simpleHtmlPurifyDataProvider()
    {
        return [
            'text without tags' => [
                <<<TEXT
This is some text without style and script
tags to make sure that some exception is not
thrown or something.
TEXT
                ,
                <<<TEXT
This is some text without style and script
tags to make sure that some exception is not
thrown or something.
TEXT
            ],
            'html with <style> and <script> tags' => [
                <<<HTML
<html dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="GENERATOR" content="MSHTML 10.00.9200.17228">
<style id="owaParaStyle">P {
	MARGIN-BOTTOM: 0px; MARGIN-TOP: 0px
}
</style>
<style id="owaParaStyle">P {MARGIN-BOTTOM: 0px; MARGIN-TOP: 0px}
    </style>
</head>
<body fPStyle="1" ocsi="0">
<div style="direction: ltr;font-family: Tahoma;color: #000000;font-size: 10pt;">no subject</div>
<script>
    console.warn('js is enabled');
   </script>
</body>
</html>
HTML
                ,
            <<<HTML
<html dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="GENERATOR" content="MSHTML 10.00.9200.17228">


</head>
<body fPStyle="1" ocsi="0">
<div style="direction: ltr;font-family: Tahoma;color: #000000;font-size: 10pt;">no subject</div>

</body>
</html>
HTML
            ]
        ];
    }
}
