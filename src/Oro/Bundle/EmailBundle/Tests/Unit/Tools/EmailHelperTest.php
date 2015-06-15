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
