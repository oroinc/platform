<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Mailer\UserTemplateEmailSender;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var UserTemplateEmailSender|MockObject
     */
    private $userTemplateEmailSender;

    /**
     * @var Processor
     */
    private $mailProcessor;

    protected function setUp()
    {
        $this->user = new User();
        $this->user
            ->setEmail('email_to@example.com')
            ->setPlainPassword('TestPassword');

        $this->userTemplateEmailSender = $this->createMock(UserTemplateEmailSender::class);
        $this->mailProcessor = new Processor($this->userTemplateEmailSender);
    }

    public function testSendChangePasswordEmail(): void
    {
        $returnValue = 1;
        $this->userTemplateEmailSender
            ->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_USER_CHANGE_PASSWORD,
                ['entity' => $this->user, 'plainPassword' => $this->user->getPlainPassword()]
            )
            ->willReturn($returnValue);

        self::assertEquals($returnValue, $this->mailProcessor->sendChangePasswordEmail($this->user));
    }

    public function testSendResetPasswordEmail(): void
    {
        $returnValue = 1;
        $this->userTemplateEmailSender
            ->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                ['entity' => $this->user]
            )
            ->willReturn($returnValue);

        self::assertEquals($returnValue, $this->mailProcessor->sendResetPasswordEmail($this->user));
    }

    public function testSendResetPasswordAsAdminEmail(): void
    {
        $returnValue = 1;
        $this->userTemplateEmailSender
            ->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
                ['entity' => $this->user]
            )
            ->willReturn($returnValue);

        self::assertEquals($returnValue, $this->mailProcessor->sendResetPasswordAsAdminEmail($this->user));
    }

    public function testSendForcedResetPasswordAsAdminEmail(): void
    {
        $returnValue = 1;
        $this->userTemplateEmailSender
            ->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_FORCE_RESET_PASSWORD,
                ['entity' => $this->user]
            )
            ->willReturn($returnValue);

        self::assertEquals($returnValue, $this->mailProcessor->sendForcedResetPasswordAsAdminEmail($this->user));
    }
}
