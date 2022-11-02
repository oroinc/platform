<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add password_changed flash message to flash bag on PasswordChangedException.
 */
class PasswordChangeExceptionListener
{
    private SessionInterface $session;

    private TranslatorInterface $translator;

    public function __construct(
        SessionInterface $session,
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
        $this->session = $session;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        if ($event->getThrowable() instanceof PasswordChangedException) {
            $this->session->getFlashBag()->add(
                'error',
                $this->translator->trans('oro.user.security.password_changed.message')
            );
        }
    }
}
