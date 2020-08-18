<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\EventListener\PasswordChangeExceptionListener;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Translation\TranslatorInterface;

class PasswordChangeExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PasswordChangeExceptionListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new PasswordChangeExceptionListener(
            $this->session,
            $this->translator
        );
    }

    public function testOnKernelExceptionNotPasswordChanged()
    {
        $event = $this->createMock(ExceptionEvent::class);
        $event->expects($this->once())
            ->method('getThrowable')
            ->willReturn(new \Exception());

        $this->session->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelException($event);
    }

    public function testOnKernelExceptionPasswordChanged()
    {
        $event = $this->createMock(ExceptionEvent::class);
        $event->expects($this->once())
            ->method('getThrowable')
            ->willReturn(new PasswordChangedException());

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
