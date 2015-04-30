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
     * @dataProvider bodiesProvider
     */
    public function testGetOnlyLastAnswer($expected, $actual, $isTextContent)
    {
        $body = new EmailBody();
        $body->setBodyContent($actual);
        $body->setBodyIsText($isTextContent);

        $this->assertEquals($expected, $this->helper->getOnlyLastAnswer($body));
    }

    /**
     * @dataProvider shortBodiesProvider
     */
    public function testGetShortBody($expected, $actual, $maxLength)
    {
        $this->assertEquals($expected, $this->helper->getShortBody($actual, $maxLength));
    }

    public static function bodiesProvider()
    {
        return array(
            array('<p>Hello</p>', '<p>Hello</p>', false),
            array('<p>Hello</p>', '<p>Hello</p><div class="quote">Other content</div>', false),
            array('<p>H</p><div class="quote">H</div>', '<p>H</p><div class="quote">H</div>', true)
        );
    }

    public static function shortBodiesProvider()
    {
        return array(
            array('abc abc abc...', 'abc abc abc abc', 13),
            array('abc abc abc abc', 'abc abc abc abc', 16),
            array('abcabcabca...', 'abcabcabcabc', 10),
        );
    }
}
