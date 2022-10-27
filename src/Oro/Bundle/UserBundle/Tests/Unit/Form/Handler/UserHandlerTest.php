<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\UserBundle\Entity\User as RealUserEntity;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Form\Handler\UserHandler;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var EmailTemplateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailTemplateManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userConfigManager;

    /** @var FlashBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $flashBag;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var UserHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->manager = $this->createMock(UserManager::class);
        $this->emailTemplateManager = $this->createMock(EmailTemplateManager::class);
        $this->userConfigManager = $this->createMock(ConfigManager::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new UserHandler(
            $this->form,
            $requestStack,
            $this->manager,
            $this->emailTemplateManager,
            $this->userConfigManager,
            $this->flashBag,
            $this->translator,
            $this->logger
        );
    }

    public function testProcessUnsupportedMethod()
    {
        $user = new User();

        $this->request->setMethod(Request::METHOD_GET);

        $this->manager->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->handler->process($user));
    }

    public function testProcess()
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod(Request::METHOD_POST);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->manager->expects($this->once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_ACTIVE);

        $this->userConfigManager->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_user.send_password_in_invitation_email', false, false, null, true],
                ['oro_notification.email_notification_sender_email', false, false, null, 'admin@example.com'],
                ['oro_notification.email_notification_sender_name', false, false, null, 'John Doe'],
            ]);

        $this->form->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['passwordGenerate', true],
                ['inviteUser', true],
            ]);

        $childForm = $this->createMock(FormInterface::class);
        $childForm->expects($this->once())
            ->method('getData')
            ->willReturn(true);
        $childForm->expects($this->once())
            ->method('getViewData')
            ->willReturn(true);

        $this->form->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['passwordGenerate', $childForm],
                ['inviteUser', $childForm],
            ]);

        $plainPassword = 'Qwerty!123%$';

        $this->manager->expects($this->once())
            ->method('generatePassword')
            ->with(10)
            ->willReturn($plainPassword);
        $this->manager->expects($this->once())
            ->method('updateUser')
            ->with($user);

        $this->emailTemplateManager->expects($this->once())
            ->method('sendTemplateEmail')
            ->with(
                From::emailAddress('admin@example.com', 'John Doe'),
                [$user],
                new EmailTemplateCriteria(UserHandler::INVITE_USER_TEMPLATE, RealUserEntity::class),
                ['user' => $user, 'password' => $plainPassword]
            )
            ->willReturn(1);

        $this->assertTrue($this->handler->process($user));
    }

    public function testProcessWithoutEmailAndWithPassword()
    {
        $user = new User();

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod(Request::METHOD_POST);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->manager->expects($this->once())
            ->method('setAuthStatus')
            ->with($user, UserManager::STATUS_ACTIVE);

        $this->userConfigManager->expects($this->once())
            ->method('get')
            ->with('oro_user.send_password_in_invitation_email', false, false, null)
            ->willReturn(false);

        $this->form->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['passwordGenerate', true],
                ['inviteUser', false],
            ]);

        $childForm = $this->createMock(FormInterface::class);
        $childForm->expects($this->once())
            ->method('getData')
            ->willReturn(false);

        $this->form->expects($this->once())
            ->method('get')
            ->with('passwordGenerate')
            ->willReturn($childForm);

        $this->manager->expects($this->never())
            ->method('generatePassword');
        $this->manager->expects($this->once())
            ->method('updateUser')
            ->with($user);

        $this->emailTemplateManager->expects($this->never())
            ->method('sendTemplateEmail');

        $this->assertTrue($this->handler->process($user));
    }
}
