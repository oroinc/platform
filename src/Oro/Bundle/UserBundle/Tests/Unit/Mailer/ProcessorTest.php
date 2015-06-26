<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Entity\User;

class ProcessorTest extends AbstractProcessorTest
{
    /** @var Processor|\PHPUnit_Framework_MockObject_MockObject */
    protected $mailProcessor;

    /** @var User */
    protected $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = new User();
        $this->user
            ->setEmail('email_to@example.com')
            ->setPlainPassword('TestPassword');

        $this->mailProcessor = new Processor(
            $this->managerRegistry,
            $this->configManager,
            $this->renderer,
            $this->emailHolderHelper,
            $this->mailer
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->user);
    }

    public function testSendChangePasswordEmail()
    {
        $this->assertSendCalled(
            Processor::TEMPLATE_USER_CHANGE_PASSWORD,
            ['entity' => $this->user, 'plainPassword' => $this->user->getPlainPassword()],
            $this->buildMessage($this->user->getEmail())
        );

        $this->mailProcessor->sendChangePasswordEmail($this->user);
    }

    public function testSendResetPasswordEmail()
    {
        $this->assertSendCalled(
            Processor::TEMPLATE_USER_RESET_PASSWORD,
            ['entity' => $this->user],
            $this->buildMessage($this->user->getEmail())
        );

        $this->mailProcessor->sendResetPasswordEmail($this->user);
    }

    public function testSendResetPasswordAsAdminEmail()
    {
        $this->assertSendCalled(
            Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
            ['entity' => $this->user],
            $this->buildMessage($this->user->getEmail())
        );

        $this->mailProcessor->sendResetPasswordAsAdminEmail($this->user);
    }
}
