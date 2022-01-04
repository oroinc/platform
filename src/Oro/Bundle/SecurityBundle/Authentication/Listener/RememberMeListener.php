<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Listener;

use Oro\Bundle\SecurityBundle\Request\CsrfProtectedRequestHelper;
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
    private CsrfProtectedRequestHelper $csrfProtectedRequestHelper;

    public function __construct(SymfonyRememberMeListener $innerListener)
    {
        $this->innerListener = $innerListener;
    }

    public function supports(Request $request): ?bool
    {
        return $this->ajaxCsrfOnlyFlag
            ? $this->csrfProtectedRequestHelper->isCsrfProtectedRequest($request)
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

    public function setCsrfProtectedRequestHelper(CsrfProtectedRequestHelper $csrfProtectedRequestHelper): void
    {
        $this->csrfProtectedRequestHelper = $csrfProtectedRequestHelper;
    }
}
