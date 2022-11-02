<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData\CustomizeFormDataProcessorTestCase;
use Oro\Bundle\UserBundle\Api\Processor\UpdateNewUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Form\FormInterface;

class UpdateNewUserTest extends CustomizeFormDataProcessorTestCase
{
    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var UpdateNewUser */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userManager = $this->createMock(UserManager::class);

        $this->context->setEvent(CustomizeFormDataContext::EVENT_POST_VALIDATE);
        $this->context->setForm($this->createMock(FormInterface::class));

        $this->processor = new UpdateNewUser($this->userManager);
    }

    public function testProcessWhenFormIsNotValid()
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->context->getForm();
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);
        $form->expects(self::never())
            ->method('getData');

        $this->userManager->expects(self::never())
            ->method('updateUser');

        $this->processor->process($this->context);
    }

    public function testProcessWhenUserDoesNotHavePassword()
    {
        $user = $this->createMock(User::class);
        $plainPassword = 'some_password';

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->context->getForm();
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($user);

        $user->expects(self::once())
            ->method('getPlainPassword')
            ->willReturn(null);
        $user->expects(self::once())
            ->method('setPlainPassword')
            ->with($plainPassword);

        $this->userManager->expects(self::once())
            ->method('generatePassword')
            ->willReturn($plainPassword);
        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with(self::identicalTo($user), self::isFalse());

        $this->context->setResult($user);
        $this->processor->process($this->context);
    }

    public function testProcessWhenUserPasswordAlreadySet()
    {
        $user = $this->createMock(User::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->context->getForm();
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($user);

        $user->expects(self::once())
            ->method('getPlainPassword')
            ->willReturn('test_password');
        $user->expects(self::never())
            ->method('setPlainPassword');

        $this->userManager->expects(self::never())
            ->method('generatePassword');
        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with(self::identicalTo($user), self::isFalse());

        $this->context->setResult($user);
        $this->processor->process($this->context);
    }
}
