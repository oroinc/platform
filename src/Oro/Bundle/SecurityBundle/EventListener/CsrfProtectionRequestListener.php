<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\SecurityBundle\Csrf\CookieTokenStorage;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Implementation of double submit cookie approach to protect application against CSRF attacks
 * On kernel.controller if it controller or action is marked for CsrfProtection with annotation
 * listeners checks that value in cookie and value in request match. Otherwise access to controller is prohibited.
 */
class CsrfProtectionRequestListener
{
    /** @var CsrfRequestManager */
    private $csrfRequestManager;

    public function __construct(CsrfRequestManager $csrfRequestManager)
    {
        $this->csrfRequestManager = $csrfRequestManager;
    }

    /**
     * Implements double submit cookie CSRF check.
     *
     * @throws AccessDeniedHttpException when route is protected against CSRF attacks and security check failed
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        $refreshCsrfToken = false;
        if (!$request->cookies->has(CsrfRequestManager::CSRF_TOKEN_ID)) {
            // if CSRF cookie was not generated yet - force generation
            $refreshCsrfToken = true;
        }

        // check CSRF Protection annotation and validate token. Refresh used token after check
        $csrProtectionAttribute = '_' . CsrfProtection::ALIAS_NAME;
        if ($request->attributes->has($csrProtectionAttribute)) {
            /** @var CsrfProtection $csrProtectionAnnotation */
            $csrProtectionAnnotation = $request->attributes->get($csrProtectionAttribute);
            if ($csrProtectionAnnotation->isEnabled()) {
                $isTokenValid = $this->csrfRequestManager->isRequestTokenValid(
                    $request,
                    $csrProtectionAnnotation->isUseRequest()
                );
                if (!$isTokenValid) {
                    throw new AccessDeniedHttpException('Invalid CSRF token');
                }

                $refreshCsrfToken = true;
            }
        }

        if ($refreshCsrfToken) {
            $this->csrfRequestManager->refreshRequestToken();
        }
    }

    /**
     * Regenerates CSRF cookie on each response.
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->has(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE)) {
            $event->getResponse()->headers->setCookie(
                $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE)
            );
        }
    }
}
