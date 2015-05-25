<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Tools\EmailHelper;

class EmailHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailHelper */
    protected $helper;

    protected function setUp()
    {
        $this->helper = new EmailHelper();
    }

    public function testGetStrippedBody()
    {
        $actualString = '<style type="text/css">H1 {border-width: 1;}</style><div class="new">test</div>';
        $expectedString = 'test';

        $this->assertEquals($expectedString, $this->helper->getStrippedBody($actualString));
    }

    /**
     * @dataProvider shortBodiesProvider
     */
    public function testGetShortBody($expected, $actual, $maxLength)
    {
        $shortBody = $this->helper->getShortBody($actual, $maxLength);
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
