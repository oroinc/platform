<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Sender\EmailTemplateSender;
use Oro\Bundle\UserBundle\Entity\User as RealUserEntity;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Form\Handler\UserHandler;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserHandlerTest extends TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    private FormInterface&MockObject $form;
    private Request $request;
    private UserManager&MockObject $manager;
    private EmailTemplateSender&MockObject $emailTemplateSender;
    private ConfigManager&MockObject $userConfigManager;
    private TranslatorInterface&MockObject $translator;
    private LoggerInterface&MockObject $logger;
    private UserHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->manager = $this->createMock(UserManager::class);
        $this->emailTemplateSender = $this->createMock(EmailTemplateSender::class);
        $this->userConfigManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new UserHandler(
            $this->form,
            $requestStack,
            $this->manager,
            $this->emailTemplateSender,
            $this->userConfigManager,
            $this->translator,
            $this->logger
        );
    }

    public function testProcessUnsupportedMethod(): void
    {
        $user = new User();

        $this->request->setMethod(Request::METHOD_GET);

        $this->manager->expects(self::never())
            ->method(self::anything());

        self::assertFalse($this->handler->process($user));
    }

    public function testProcess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod(Request::METHOD_POST);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->manager->expects(self::once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_ACTIVE);

        $this->userConfigManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_user.send_password_in_invitation_email', false, false, null, true],
                ['oro_notification.email_notification_sender_email', false, false, null, 'admin@example.com'],
                ['oro_notification.email_notification_sender_name', false, false, null, 'John Doe'],
            ]);

        $this->form->expects(self::exactly(2))
            ->method('has')
            ->willReturnMap([
                ['passwordGenerate', true],
                ['inviteUser', true],
            ]);

        $childForm = $this->createMock(FormInterface::class);
        $childForm->expects(self::once())
            ->method('getData')
            ->willReturn(true);
        $childForm->expects(self::once())
            ->method('getViewData')
            ->willReturn(true);

        $this->form->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['passwordGenerate', $childForm],
                ['inviteUser', $childForm],
            ]);

        $plainPassword = 'Qwerty!123%$';

        $this->manager->expects(self::once())
            ->method('generatePassword')
            ->with(10)
            ->willReturn($plainPassword);
        $this->manager->expects(self::once())
            ->method('updateUser')
            ->with($user);

        $this->emailTemplateSender->expects(self::once())
            ->method('sendEmailTemplate')
            ->with(
                From::emailAddress('admin@example.com', 'John Doe'),
                $user,
                new EmailTemplateCriteria(UserHandler::INVITE_USER_TEMPLATE, RealUserEntity::class),
                ['entity' => $user, 'user' => $user, 'password' => $plainPassword]
            )
            ->willReturn($this->createMock(EmailUser::class));

        self::assertTrue($this->handler->process($user));
    }

    public function testProcessWithoutEmailAndWithPassword(): void
    {
        $user = new User();

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod(Request::METHOD_POST);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->manager->expects(self::once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_ACTIVE);

        $this->userConfigManager->expects(self::once())
            ->method('get')
            ->with('oro_user.send_password_in_invitation_email', false, false, null)
            ->willReturn(false);

        $this->form->expects(self::exactly(2))
            ->method('has')
            ->willReturnMap([
                ['passwordGenerate', true],
                ['inviteUser', false],
            ]);

        $childForm = $this->createMock(FormInterface::class);
        $childForm->expects(self::once())
            ->method('getData')
            ->willReturn(false);

        $this->form->expects(self::once())
            ->method('get')
            ->with('passwordGenerate')
            ->willReturn($childForm);

        $this->manager->expects(self::never())
            ->method('generatePassword');
        $this->manager->expects(self::once())
            ->method('updateUser')
            ->with($user);

        $this->emailTemplateSender->expects(self::never())
            ->method('sendEmailTemplate');

        self::assertTrue($this->handler->process($user));
    }
}
