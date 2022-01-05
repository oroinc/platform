<?php

namespace Oro\Bundle\SecurityBundle\Request;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * The helper class to check whether the current request is a CSRF protected request or not.
 */
class CsrfProtectedRequestHelper
{
    private CsrfRequestManager $csrfRequestManager;

    public function __construct(CsrfRequestManager $csrfRequestManager)
    {
        $this->csrfRequestManager = $csrfRequestManager;
    }

    /**
     * Checks whether the request is a CSRF protected request
     * (cookies has the session cookie and the request has "X-CSRF-Header" header with valid CSRF token).
     */
    public function isCsrfProtectedRequest(Request $request): bool
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
