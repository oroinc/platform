<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Tools\EmailHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailHelperTest extends \PHPUnit_Framework_TestCase
{

    /** @var  SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var EmailHelper */
    protected $helper;

    protected function setUp()
    {
        $securityFacadeLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacadeLink->expects($this->once())
            ->method('getService')
            ->willReturn($this->securityFacade);
        $this->helper = new EmailHelper($securityFacadeLink);
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

    public function testIsEmailActionGranted()
    {
        $entity = new Email();
        $entity->addEmailUser(new EmailUser());

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->assertTrue($this->helper->isEmailActionGranted('VIEW', $entity));
    }
}
