<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;

class EmailAddressHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailAddressHelper */
    protected $helper;

    protected function setUp()
    {
        $this->helper = new EmailAddressHelper();
    }

    /**
     * @dataProvider emailAddressProvider
     */
    public function testExtractPureEmailAddress($fullEmailAddress, $pureEmailAddress, $name)
    {
        $this->assertEquals($pureEmailAddress, $this->helper->extractPureEmailAddress($fullEmailAddress));
    }

    /**
     * @dataProvider emailAddressProvider
     */
    public function testExtractEmailAddressName($fullEmailAddress, $pureEmailAddress, $name)
    {
        $this->assertEquals($name, $this->helper->extractEmailAddressName($fullEmailAddress));
    }

    /**
     * @dataProvider emailAddressesProvider
     */
    public function testExtractEmailAddresses($src, $expected)
    {
        $this->assertEquals($expected, $this->helper->extractEmailAddresses($src));
    }

    /**
     * @dataProvider buildFullEmailAddressProvider
     */
    public function testBuildFullEmailAddress($pureEmailAddress, $name, $fullEmailAddress)
    {
        $this->assertEquals($fullEmailAddress, $this->helper->buildFullEmailAddress($pureEmailAddress, $name));
    }

    /**
     * @dataProvider isFullEmailAddressProvider
     */
    public function testIsFullEmailAddress($emailAddress, $isFull)
    {
        $this->assertEquals($isFull, $this->helper->isFullEmailAddress($emailAddress));
    }

    public static function emailAddressProvider()
    {
        return array(
            array('john@example.com', 'john@example.com', ''),
            array('<john@example.com>', 'john@example.com', ''),
            array('John Smith <john@example.com>', 'john@example.com', 'John Smith'),
            array('"John Smith" <john@example.com>', 'john@example.com', 'John Smith'),
            array('\'John Smith\' <john@example.com>', 'john@example.com', 'John Smith'),
            array('John Smith on behaf <john@example.com>', 'john@example.com', 'John Smith on behaf'),
            array('"john@example.com" <john@example.com>', 'john@example.com', 'john@example.com'),
        );
    }

    public static function buildFullEmailAddressProvider()
    {
        return array(
            array(null, null, ''),
            array('', '', ''),
            array('john@example.com', null, 'john@example.com'),
            array('john@example.com', '', 'john@example.com'),
            array('john@example.com', null, 'john@example.com'),
            array('john@example.com', 'John Smith', 'John Smith <john@example.com>'),
            array(' john@example.com ', ' John Smith ', 'John Smith <john@example.com>'),
        );
    }

    public static function isFullEmailAddressProvider()
    {
        return array(
            array(null, false),
            array('', false),
            array('john@example.com', false),
            array('<john@example.com>', true),
            array('John Smith <john@example.com>', true),
            array('"John Smith" <john@example.com>', true),
        );
    }

    public function emailAddressesProvider()
    {
        $emailObj = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailInterface');
        $emailObj->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('john@example.com'));

        return array(
            array('', array()),
            array(array(), array()),
            array('john@example.com', array('john@example.com')),
            array(array('john@example.com'), array('john@example.com')),
            array(array($emailObj), array('john@example.com')),
        );
    }
}
