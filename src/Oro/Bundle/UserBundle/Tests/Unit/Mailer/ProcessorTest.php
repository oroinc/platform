<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Mailer\UserTemplateEmailSender;

class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIRMATION_TOKEN = 'aa11bb22cc33';

    /** @var User */
    private $user;

    /** @var UserTemplateEmailSender|\PHPUnit\Framework\MockObject\MockObject */
    private $userTemplateEmailSender;

    /** @var Processor */
    private $mailProcessor;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user
            ->setEmail('email_to@example.com')
            ->setPlainPassword('TestPassword')
            ->setConfirmationToken(self::CONFIRMATION_TOKEN);

        $this->userTemplateEmailSender = $this->createMock(UserTemplateEmailSender::class);
        $this->mailProcessor = new Processor($this->userTemplateEmailSender);
    }

    public function testSendChangePasswordEmail(): void
    {
        $returnValue = 1;
        $this->userTemplateEmailSender->expects($this->once())
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
        $this->userTemplateEmailSender->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                [
                    'entity' => $this->user,
                    Processor::CONFIRMATION_TOKEN_TEMPLATE_PARAM => self::CONFIRMATION_TOKEN,
                ]
            )
            ->willReturn($returnValue);

        self::assertEquals($returnValue, $this->mailProcessor->sendResetPasswordEmail($this->user));
    }

    public function testSendResetPasswordEmailWithoutConfirmationToken(): void
    {
        $this->user->setConfirmationToken(null);

        $this->userTemplateEmailSender->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                [
                    'entity' => $this->user,
                    Processor::CONFIRMATION_TOKEN_TEMPLATE_PARAM => null,
                ]
            )
            ->willReturn(1);

        self::assertEquals(1, $this->mailProcessor->sendResetPasswordEmail($this->user));
    }

    public function testSendForcedResetPasswordAsAdminEmail(): void
    {
        $returnValue = 1;
        $this->userTemplateEmailSender->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_FORCE_RESET_PASSWORD,
                [
                    'entity' => $this->user,
                    Processor::CONFIRMATION_TOKEN_TEMPLATE_PARAM => self::CONFIRMATION_TOKEN,
                ]
            )
            ->willReturn($returnValue);

        self::assertEquals($returnValue, $this->mailProcessor->sendForcedResetPasswordAsAdminEmail($this->user));
    }

    public function testSendForcedResetPasswordAsAdminEmailWithoutConfirmationToken(): void
    {
        $this->user->setConfirmationToken(null);

        $this->userTemplateEmailSender->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::TEMPLATE_FORCE_RESET_PASSWORD,
                [
                    'entity' => $this->user,
                    Processor::CONFIRMATION_TOKEN_TEMPLATE_PARAM => null,
                ]
            )
            ->willReturn(1);

        self::assertEquals(1, $this->mailProcessor->sendForcedResetPasswordAsAdminEmail($this->user));
    }
}
