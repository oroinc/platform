<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Util;

use Oro\Bundle\EmailBundle\Entity\Util\EmailUtil;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;

class EmailUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider emailAddressProvider
     */
    public function testExtractPureEmailAddress($fullEmailAddress, $pureEmailAddress, $name)
    {
        $this->assertEquals($pureEmailAddress, EmailUtil::extractPureEmailAddress($fullEmailAddress));
    }

    /**
     * @dataProvider emailAddressProvider
     */
    public function testExtractEmailAddressName($fullEmailAddress, $pureEmailAddress, $name)
    {
        $this->assertEquals($name, EmailUtil::extractEmailAddressName($fullEmailAddress));
    }

    /**
     * @dataProvider emailAddressesProvider
     */
    public function testExtractEmailAddresses($src, $expected)
    {
        $this->assertEquals($expected, EmailUtil::extractEmailAddresses($src));
    }

    /**
     * @dataProvider buildFullEmailAddressProvider
     */
    public function testBuildFullEmailAddress($pureEmailAddress, $name, $fullEmailAddress)
    {
        $this->assertEquals($fullEmailAddress, EmailUtil::buildFullEmailAddress($pureEmailAddress, $name));
    }

    /**
     * @dataProvider isFullEmailAddressProvider
     */
    public function testIsFullEmailAddress($emailAddress, $isFull)
    {
        $this->assertEquals($isFull, EmailUtil::isFullEmailAddress($emailAddress));
    }

    /**
     * @dataProvider hasEmailProvider
     */
    public function testHasEmail($objectOrClassName, $expected)
    {
        $this->assertEquals($expected, EmailUtil::hasEmail($objectOrClassName));
    }

    /**
     * @dataProvider getEmailProvider
     */
    public function testGetEmail($object, $expected)
    {
        $this->assertEquals($expected, EmailUtil::getEmail($object));
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

    public function hasEmailProvider()
    {
        return array(
            'null'                                => array(null, false),
            'not obj and not str'                 => array(123, false),
            'obj without email'                   => array(new \stdClass(), false),
            'obj implements EmailHolderInterface' => array(new TestEmailHolder(), true),
            'obj has getEmail method'             => array(new TestUser(), true),
            'cls without email'                   => array('stdClass', false),
            'cls implements EmailHolderInterface' => array(
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder',
                true
            ),
            'cls has getEmail method'             => array(
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser',
                true
            ),
        );
    }

    public function getEmailProvider()
    {
        return array(
            'null'                                => array(null, null),
            'not obj'                             => array(
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder',
                null
            ),
            'obj implements EmailHolderInterface' => array(
                new TestEmailHolder('test@example.com'),
                'test@example.com'
            ),
            'obj has getEmail method'             => array(
                new TestUser('test@example.com'),
                'test@example.com'
            ),
        );
    }
}
