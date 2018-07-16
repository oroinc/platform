<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

class EmailAddressHelperTest extends \PHPUnit\Framework\TestCase
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
            ['<john@example.com> (Contact)', 'john@example.com', ''],
            ['John Smith <john@example.com> (Contact)', 'john@example.com', 'John Smith'],
            ['"John Smith" <john@example.com> (Contact)', 'john@example.com', 'John Smith'],
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
        $emailObj = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailInterface');
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

    /**
     * @dataProvider truncateFullEmailAddressProvider
     */
    public function testTruncateFullEmailAddress($email, $maxLength, $expected)
    {
        $this->assertEquals($expected, $this->helper->truncateFullEmailAddress($email, $maxLength));
    }

    public static function truncateFullEmailAddressProvider()
    {
        return [
            ['john@example.com', 255, 'john@example.com'],
            ['john@example.com', 16, 'john@example.com'],
            ['john@example.com', 10, 'john@example.com'],
            ['<john@example.com>', 255, '<john@example.com>'],
            ['<john@example.com>', 18, '<john@example.com>'],
            ['<john@example.com>', 10, '<john@example.com>'],
            ['John Smith <john@example.com>', 255, 'John Smith <john@example.com>'],
            ['John Smith <john@example.com>', 29, 'John Smith <john@example.com>'],
            ['John Smith <john@example.com>', 28, 'John S... <john@example.com>'],
            ['John Smith <john@example.com>', 27, 'John ... <john@example.com>'],
            ['John Smith <john@example.com>', 23, 'J... <john@example.com>'],
            ['John Smith <john@example.com>', 22, 'Joh <john@example.com>'],
            ['John Smith <john@example.com>', 21, 'Jo <john@example.com>'],
            ['John Smith <john@example.com>', 20, 'J <john@example.com>'],
            ['John Smith <john@example.com>', 19, '<john@example.com>'],
            ['John Smith <john@example.com>', 18, '<john@example.com>'],
            ['John Smith <john@example.com>', 10, '<john@example.com>'],
            ['"John Smith" <john@example.com>', 255, '"John Smith" <john@example.com>'],
            ['"John Smith" <john@example.com>', 31, '"John Smith" <john@example.com>'],
            ['"John Smith" <john@example.com>', 30, '"John S..." <john@example.com>'],
            ['"John Smith" <john@example.com>', 29, '"John ..." <john@example.com>'],
            ['"John Smith" <john@example.com>', 25, '"J..." <john@example.com>'],
            ['"John Smith" <john@example.com>', 24, '"Joh" <john@example.com>'],
            ['"John Smith" <john@example.com>', 23, '"Jo" <john@example.com>'],
            ['"John Smith" <john@example.com>', 22, '"J" <john@example.com>'],
            ['"John Smith" <john@example.com>', 21, '<john@example.com>'],
            ['"John Smith" <john@example.com>', 20, '<john@example.com>'],
            ['"John Smith" <john@example.com>', 19, '<john@example.com>'],
            ['"John Smith" <john@example.com>', 18, '<john@example.com>'],
            ['"John Smith" <john@example.com>', 10, '<john@example.com>'],
            ['John Smith <john@example.com> (Contact)', 255, 'John Smith <john@example.com> (Contact)'],
            ['John Smith <john@example.com> (Contact)', 39, 'John Smith <john@example.com> (Contact)'],
            ['John Smith <john@example.com> (Contact)', 38, 'John Smith <john@example.com>'],
            ['John Smith <john@example.com> (Contact)', 29, 'John Smith <john@example.com>'],
            ['John Smith <john@example.com> (Contact)', 28, 'John S... <john@example.com>'],
            ['"John Smith" <john@example.com> (Contact)', 255, '"John Smith" <john@example.com> (Contact)'],
            ['"John Smith" <john@example.com> (Contact)', 41, '"John Smith" <john@example.com> (Contact)'],
            ['"John Smith" <john@example.com> (Contact)', 40, '"John Smith" <john@example.com>'],
            ['"John Smith" <john@example.com> (Contact)', 31, '"John Smith" <john@example.com>'],
            ['"John Smith" <john@example.com> (Contact)', 30, '"John S..." <john@example.com>'],
        ];
    }
}
