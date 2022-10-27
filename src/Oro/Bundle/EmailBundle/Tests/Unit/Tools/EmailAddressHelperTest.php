<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailAddressHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailAddressHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new EmailAddressHelper();
    }

    /**
     * @dataProvider emailAddressProvider
     */
    public function testExtractPureEmailAddress(string $fullEmailAddress, string $pureEmailAddress, string $name)
    {
        $this->assertEquals($pureEmailAddress, $this->helper->extractPureEmailAddress($fullEmailAddress));
    }

    /**
     * @dataProvider emailAddressProvider
     */
    public function testExtractEmailAddressName(string $fullEmailAddress, string $pureEmailAddress, string $name)
    {
        $this->assertEquals($name, $this->helper->extractEmailAddressName($fullEmailAddress));
    }

    /**
     * @dataProvider buildFullEmailAddressProvider
     */
    public function testBuildFullEmailAddress(?string $pureEmailAddress, ?string $name, string $fullEmailAddress)
    {
        $this->assertEquals($fullEmailAddress, $this->helper->buildFullEmailAddress($pureEmailAddress, $name));
    }

    /**
     * @dataProvider isFullEmailAddressProvider
     */
    public function testIsFullEmailAddress(?string $emailAddress, bool $isFull)
    {
        $this->assertEquals($isFull, $this->helper->isFullEmailAddress($emailAddress));
    }

    /**
     * @dataProvider extractEmailAddressFirstNameProvider
     */
    public function testExtractEmailAddressFirstName(string $emailAddress, string $expected)
    {
        $this->assertEquals($expected, $this->helper->extractEmailAddressFirstName($emailAddress));
    }

    /**
     * @dataProvider extractEmailAddressLastNameProvider
     */
    public function testExtractEmailAddressLastName(string $emailAddress, string $expected)
    {
        $this->assertEquals($expected, $this->helper->extractEmailAddressLastName($emailAddress));
    }

    public static function emailAddressProvider(): array
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

    public static function buildFullEmailAddressProvider(): array
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

    public static function isFullEmailAddressProvider(): array
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

    public static function extractEmailAddressFirstNameProvider(): array
    {
        return [
            ['John Smith IV. <john@example.com>', 'John'],
            ['"John Smith" <john@example.com>', 'John'],
            ['John <john@example.com>', 'John'],
            ['john.smith@example.com', 'john'],
            ['john@example.com', 'john'],
        ];
    }

    public static function extractEmailAddressLastNameProvider(): array
    {
        return [
            ['John Smith IV. <john@example.com>', 'Smith IV.'],
            ['"John Smith" <john@example.com>', 'Smith'],
            ['John <john@example.com>', 'example.com'],
            ['john.smith@example.com', 'smith'],
            ['john@example.com', 'example.com'],
        ];
    }

    /**
     * @dataProvider truncateFullEmailAddressProvider
     */
    public function testTruncateFullEmailAddress(string $email, int $maxLength, string $expected)
    {
        $this->assertEquals($expected, $this->helper->truncateFullEmailAddress($email, $maxLength));
    }

    public static function truncateFullEmailAddressProvider(): array
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
