<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Listener;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Firewall\AbstractListener;
use Symfony\Component\Security\Http\Firewall\LegacyListenerTrait;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\Firewall\RememberMeListener as OrigRememberMeListener;

/**
 * 'Remember Me' security listener override to have ability to process CSRF protected AJAX requests only
 */
class RememberMeListener extends AbstractListener implements ListenerInterface
{
    use LegacyListenerTrait;

    /** @var OrigRememberMeListener */
    private $innerListener;

    /** @var bool */
    private $ajaxCsrfOnlyFlag = false;

    /** @var CsrfRequestManager */
    private $csrfRequestManager;

    /** @var SessionInterface|null */
    private $session;

    public function __construct(
        OrigRememberMeListener $innerListener,
        SessionInterface $session = null
    ) {
        $this->innerListener = $innerListener;
        $this->session = $session;
    }

    /**
     * {@inheritdsoc}
     */
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
            null !== $this->session
            && $request->cookies->has($this->session->getName())
            && (
                (!$isGetRequest && $this->csrfRequestManager->isRequestTokenValid($request))
                || ($isGetRequest && $request->headers->has(CsrfRequestManager::CSRF_HEADER))
            );
    }
}
