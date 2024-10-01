<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Authentication\Listener;

use Oro\Bundle\SecurityBundle\Request\CsrfProtectedRequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Authenticator\RememberMeAuthenticator;
use Symfony\Component\Security\Http\Firewall\AbstractListener;

/**
 * 'Remember Me' security listener override to have ability to process CSRF protected AJAX requests only
 */
class RememberMeListener extends AbstractListener
{
    public function __construct(
        private RememberMeAuthenticator $innerRememberMeAuthenticator,
        private CsrfProtectedRequestHelper $csrfProtectedRequestHelper,
        private readonly bool $csrfProtectedAjaxOnly = false
    ) {
    }

    #[\Override]
    public function supports(Request $request): bool
    {
        if ($this->csrfProtectedAjaxOnly && !$this->csrfProtectedRequestHelper->isCsrfProtectedRequest($request)) {
            return false;
        }

        return $this->innerRememberMeAuthenticator->supports($request);
    }

    #[\Override]
    public function authenticate(RequestEvent $event): void
    {
        $this->innerRememberMeAuthenticator->authenticate($event->getRequest());
    }
}
