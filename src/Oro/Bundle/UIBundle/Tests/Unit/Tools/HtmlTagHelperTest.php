<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

class HtmlTagHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var HtmlTagHelper */
    protected $helper;

    /** @var HtmlTagProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $htmlTagProvider;

    protected function setUp()
    {
        $this->htmlTagProvider = $this->getMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $this->helper = new HtmlTagHelper($this->htmlTagProvider);
    }

    public function testGetStripped()
    {
        $actualString = '<div class="new">test1 test2</div><div class="new">test3   test4</div>';
        $expectedString = 'test1 test2 test3 test4';

        $this->assertEquals($expectedString, $this->helper->stripTags($actualString));
    }

    /**
     * @dataProvider shortStringProvider
     */
    public function testGetShort($expected, $actual, $maxLength)
    {
        $shortBody = $this->helper->shorten($actual, $maxLength);
        $this->assertEquals($expected, $shortBody);
    }

    public static function shortStringProvider()
    {
        return [
            ['абв абв абв', 'абв абв абв абв ', 12],
            ['abc abc abc', 'abc abc abc abc ', 12],
            ['abc abc', 'abc abc abc abc abc', 8],
            ['abcab', 'abcabcabcabc', 5],
        ];
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
<div style="direction: ltr;font-family: Tahoma;color: #000000;font-size: 10pt;">no subject2</div>
<span>same line</span><span>same line2</span>
<p>same line</p><p>same line2</p>
</body>
</html>
STR;

        $expected = <<<STR
no subject
no subject2
same linesame line2
same linesame line2
STR;

        $this->assertEquals($expected, $this->helper->purify($testString));
    }
}
