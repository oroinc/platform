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

    public function testGetStrippedBody()
    {
        $actualString = '<style type="text/css">H1 {border-width: 1;}</style><div class="new">test</div>';
        $expectedString = 'test';

        $this->assertEquals($expectedString, $this->helper->getStripped($actualString));
    }

    /**
     * @dataProvider shortBodiesProvider
     */
    public function testGetShortBody($expected, $actual, $maxLength)
    {
        $shortBody = $this->helper->getShort($actual, $maxLength);
        $this->assertEquals($expected, $shortBody);
    }

    public static function bodiesProvider()
    {
        return [
            ['<p>Hello</p>', '<p>Hello</p>', false],
            ['<p>Hello</p>', '<p>Hello</p><div class="quote">Other content</div>', false],
            ['<p>H</p><div class="quote">H</div>', '<p>H</p><div class="quote">H</div>', true]
        ];
    }

    public static function shortBodiesProvider()
    {
        return [
            ['abc abc abc', 'abc abc abc abc ', 12],
            ['abc abc', 'abc abc abc abc abc', 8],
            ['abcab', 'abcabcabcabc', 5],
        ];
    }
}
