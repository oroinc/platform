<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Entity\User;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailProcessor;

    /**
     * @var User
     */
    protected $user;

    protected function setUp()
    {
        $this->user = new User();

        $this->mailProcessor = $this->getMockBuilder('Oro\Bundle\UserBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->setMethods(['getEmailTemplateAndSendEmail'])
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->user, $this->mailProcessor);
    }

    public function testSendChangePasswordEmail()
    {
        $password = 'TestPassword';

        $this->user->setPlainPassword($password);

        $this->mailProcessor->expects($this->once())
            ->method('getEmailTemplateAndSendEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_USER_CHANGE_PASSWORD,
                ['entity' => $this->user, 'plainPassword' => $password]
            )
            ->willReturn(true);

        $this->assertTrue($this->mailProcessor->sendChangePasswordEmail($this->user));
    }

    public function testSendResetPasswordEmail()
    {
        $this->mailProcessor->expects($this->once())
            ->method('getEmailTemplateAndSendEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                ['entity' => $this->user]
            )
            ->willReturn(true);

        $this->assertTrue($this->mailProcessor->sendResetPasswordEmail($this->user));
    }

    public function testSendResetPasswordAsAdminEmail()
    {
        $this->mailProcessor->expects($this->once())
            ->method('getEmailTemplateAndSendEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
                ['entity' => $this->user]
            )
            ->willReturn(true);

        $this->assertTrue($this->mailProcessor->sendResetPasswordAsAdminEmail($this->user));
    }
}
