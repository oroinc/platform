<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add password_changed flash message to flash bag on PasswordChangedException.
 */
class PasswordChangeExceptionListener
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator
    ) {
    }

    public function onKernelException(ExceptionEvent $event)
    {
        if ($event->getThrowable() instanceof PasswordChangedException) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',
                $this->translator->trans('oro.user.security.password_changed.message')
            );
        }
    }
}
