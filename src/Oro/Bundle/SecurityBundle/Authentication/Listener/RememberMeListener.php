<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Listener;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Firewall\AbstractListener;
use Symfony\Component\Security\Http\Firewall\RememberMeListener as SymfonyRememberMeListener;

/**
 * 'Remember Me' security listener override to have ability to process CSRF protected AJAX requests only
 */
class RememberMeListener extends AbstractListener
{
    private SymfonyRememberMeListener $innerListener;

    private bool $ajaxCsrfOnlyFlag = false;

    private CsrfRequestManager $csrfRequestManager;

    public function __construct(SymfonyRememberMeListener $innerListener)
    {
        $this->innerListener = $innerListener;
    }

    public function supports(Request $request): ?bool
    {
        return $this->ajaxCsrfOnlyFlag
            ? $this->isCsrfProtectedRequest($request)
            : $this->innerListener->supports($request);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(RequestEvent $event): void
    {
        $this->innerListener->authenticate($event);
    }

    public function switchToProcessAjaxCsrfOnlyRequest(): void
    {
        $this->ajaxCsrfOnlyFlag = true;
    }

    public function setCsrfRequestManager(CsrfRequestManager $csrfRequestManager): void
    {
        $this->csrfRequestManager = $csrfRequestManager;
    }

    /**
     * Checks whether the request is CSRF protected request
     * (cookies has the session cookie and the request has "X-CSRF-Header" header with valid CSRF token).
     */
    private function isCsrfProtectedRequest(Request $request): bool
    {
        $isGetRequest = $request->isMethod('GET');

        return
            $request->hasSession()
            && $request->cookies->has($request->getSession()->getName())
            && (
                (!$isGetRequest && $this->csrfRequestManager->isRequestTokenValid($request))
                || ($isGetRequest && $request->headers->has(CsrfRequestManager::CSRF_HEADER))
            );
    }
}
