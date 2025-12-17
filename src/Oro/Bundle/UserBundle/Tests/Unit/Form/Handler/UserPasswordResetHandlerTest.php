<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;
use Oro\Bundle\UserBundle\Form\Handler\UserPasswordResetHandler;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserPasswordResetHandlerTest extends TestCase
{
    private UserManager|MockObject $userManager;
    private TranslatorInterface|MockObject $translator;
    private LoggerInterface|MockObject $logger;
    private UserLoggingInfoProviderInterface|MockObject $userLoggingInfoProvider;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private UserPasswordResetHandler $handler;
    private int $ttl = 3600;

    #[\Override]
    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->userLoggingInfoProvider = $this->createMock(UserLoggingInfoProviderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new UserPasswordResetHandler(
            $this->userManager,
            $this->translator,
            $this->logger,
            $this->userLoggingInfoProvider,
            $this->ttl,
            $this->eventDispatcher
        );
    }

    public function testProcessWithGetRequest(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => Request::METHOD_GET]);
        $form = $this->createMock(FormInterface::class);

        $result = $this->handler->process($form, $request);

        $this->assertNull($result);
    }

    public function testProcessWithNotSubmittedForm(): void
    {
        $request = $this->getRequest();
        $form = $this->createMock(FormInterface::class);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false);

        $result = $this->handler->process($form, $request);

        $this->assertNull($result);
    }

    public function testProcessWithInvalidForm(): void
    {
        $request = $this->getRequest();
        $form = $this->createMock(FormInterface::class);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $result = $this->handler->process($form, $request);

        $this->assertNull($result);
    }

    public function testProcessWithNonExistentUser(): void
    {
        $request = $this->getRequest();
        $form = $this->configureValidSubmittedForm($request, 'nonexistent_user');

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with('nonexistent_user')
            ->willReturn(null);

        $result = $this->handler->process($form, $request);

        $this->assertEquals('nonexistent_user', $result);
    }

    public function testProcessWithDisabledUser(): void
    {
        $request = $this->getRequest();
        $form = $this->configureValidSubmittedForm($request, 'disabled_user');

        $user = new User();
        $user->setEnabled(false);
        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with('disabled_user')
            ->willReturn($user);

        $result = $this->handler->process($form, $request);

        $this->assertEquals('disabled_user', $result);
    }

    public function testProcessWithExistingUserButRequestNotExpired(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $user->expects($this->once())
            ->method('getEmail')
            ->willReturn('test@example.com');

        $user->expects($this->once())
            ->method('isPasswordRequestNonExpired')
            ->with($this->ttl)
            ->willReturn(true);

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with('test_user')
            ->willReturn($user);

        $this->logger->expects($this->once())
            ->method('notice');

        $this->userLoggingInfoProvider->expects($this->once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn([]);

        $request = $this->getRequest();
        $form = $this->configureValidSubmittedForm($request, 'test_user');

        $result = $this->handler->process($form, $request);

        $this->assertEquals('test_user', $result);
    }

    public function testProcessWithEmailSendingError(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $user->expects($this->once())
            ->method('getEmail')
            ->willReturn('test@example.com');

        $user->expects($this->once())
            ->method('isPasswordRequestNonExpired')
            ->with($this->ttl)
            ->willReturn(false);

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with('test_user')
            ->willReturn($user);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(PasswordChangeEvent::class),
                PasswordChangeEvent::BEFORE_PASSWORD_RESET
            )
            ->willReturnArgument(0);

        $this->userManager->expects($this->once())
            ->method('sendResetPasswordEmail')
            ->willThrowException(new \Exception());

        $this->logger->expects($this->once())
            ->method('error');

        $session = $this->createMock(Session::class);
        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('warn', 'translation');

        $session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $request = $this->getRequest();
        $request->setSession($session);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.email.handler.unable_to_send_email')
            ->willReturn('translation');

        $form = $this->configureValidSubmittedForm($request, 'test_user');
        $result = $this->handler->process($form, $request);

        $this->assertNull($result);
    }

    public function testProcessWhenEventDenies(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $user->expects($this->never())
            ->method('getEmail');

        $user->expects($this->never())
            ->method('isPasswordRequestNonExpired');

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with('test_user')
            ->willReturn($user);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(PasswordChangeEvent::class),
                PasswordChangeEvent::BEFORE_PASSWORD_RESET
            )
            ->willReturnCallback(function (PasswordChangeEvent $event) {
                $event->disablePasswordChange('Test reason');
                return $event;
            });

        $this->logger->expects($this->once())
            ->method('notice')
            ->with(
                $this->stringContains('Password reset request denied'),
                $this->anything()
            );

        $this->userLoggingInfoProvider->expects($this->once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn([]);

        $this->userManager->expects($this->never())
            ->method('sendResetPasswordEmail');

        $session = $this->createMock(Session::class);
        $session->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [UserPasswordResetHandler::SESSION_PASSWORD_RESET_UNAVAILABLE, true],
                [UserPasswordResetHandler::SESSION_PASSWORD_RESET_UNAVAILABLE_MESSAGE, 'Test reason']
            );

        $request = $this->getRequest();
        $request->setSession($session);

        $form = $this->configureValidSubmittedForm($request, 'test_user');
        $result = $this->handler->process($form, $request);

        $this->assertEquals('test_user', $result);
    }

    private function getRequest(): Request
    {
        return new Request([], [], [], [], [], ['REQUEST_METHOD' => Request::METHOD_POST]);
    }

    private function configureValidSubmittedForm(Request $request, string $username): MockObject|FormInterface
    {
        $form = $this->createMock(FormInterface::class);

        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['username', $this->createConfiguredMock(FormInterface::class, ['getData' => $username])],
                ['frontend', $this->createConfiguredMock(FormInterface::class, ['getData' => '1'])],
            ]);

        return $form;
    }
}
