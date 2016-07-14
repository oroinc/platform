<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

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

    /**
     * @dataProvider extractEmailAddressFirstNameProvider
     */
    public function testExtractEmailAddressFirstName($emailAddress, $expected)
    {
        $this->assertEquals($expected, $this->helper->extractEmailAddressFirstName($emailAddress));
    }

    /**
     * @dataProvider extractEmailAddressLastNameProvider
     */
    public function testExtractEmailAddressLastName($emailAddress, $expected)
    {
        $this->assertEquals($expected, $this->helper->extractEmailAddressLastName($emailAddress));
    }

    public static function emailAddressProvider()
    {
        return [
            ['john@example.com', 'john@example.com', ''],
            ['<john@example.com>', 'john@example.com', ''],
            ['John Smith <john@example.com>', 'john@example.com', 'John Smith'],
            ['"John Smith" <john@example.com>', 'john@example.com', 'John Smith'],
            ['\'John Smith\' <john@example.com>', 'john@example.com', 'John Smith'],
            ['John Smith on behaf <john@example.com>', 'john@example.com', 'John Smith on behaf'],
            ['"john@example.com" <john@example.com>', 'john@example.com', 'john@example.com'],
        ];
    }

    public static function buildFullEmailAddressProvider()
    {
        return [
            [null, null, ''],
            ['', '', ''],
            ['john@example.com', null, 'john@example.com'],
            ['john@example.com', '', 'john@example.com'],
            ['john@example.com', null, 'john@example.com'],
            ['john@example.com', 'John Smith', '"John Smith" <john@example.com>'],
            [' john@example.com ', ' John Smith ', '"John Smith" <john@example.com>'],
        ];
    }

    public static function isFullEmailAddressProvider()
    {
        return [
            [null, false],
            ['', false],
            ['john@example.com', false],
            ['<john@example.com>', true],
            ['John Smith <john@example.com>', true],
            ['"John Smith" <john@example.com>', true],
        ];
    }

    public function emailAddressesProvider()
    {
        $emailObj = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailInterface');
        $emailObj->expects($this->any())->method('getEmail')->will($this->returnValue('john@example.com'));

        return [
            ['', []],
            [[], []],
            ['john@example.com', ['john@example.com']],
            [['john@example.com'], ['john@example.com']],
            [[$emailObj], ['john@example.com']],
        ];
    }

    public static function extractEmailAddressFirstNameProvider()
    {
        return [
            ['John Smith IV. <john@example.com>',   'John'],
            ['"John Smith" <john@example.com>',     'John'],
            ['John <john@example.com>',             'John'],
            ['john.smith@example.com',              'john'],
            ['john@example.com',                    'john'],
        ];
    }

    public static function extractEmailAddressLastNameProvider()
    {
        return [
            ['John Smith IV. <john@example.com>',   'Smith IV.'],
            ['"John Smith" <john@example.com>',     'Smith'],
            ['John <john@example.com>',             'example.com'],
            ['john.smith@example.com',              'smith'],
            ['john@example.com',                    'example.com'],
        ];
    }
}
