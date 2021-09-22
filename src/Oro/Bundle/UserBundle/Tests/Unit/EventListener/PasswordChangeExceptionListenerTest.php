<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\EventListener\PasswordChangeExceptionListener;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordChangeExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    private SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    private PasswordChangeExceptionListener $listener;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new PasswordChangeExceptionListener(
            $this->session,
            $this->translator
        );
    }

    public function testOnKernelExceptionNotPasswordChanged(): void
    {
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new \Exception()
        );

        $this->session->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelException($event);
    }

    public function testOnKernelExceptionPasswordChanged(): void
    {
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new PasswordChangedException()
        );

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturnArgument(0);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'oro.user.security.password_changed.message');

        $this->listener->onKernelException($event);
    }
}
