<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\SecurityBundle\Csrf\CookieTokenStorage;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Implementation of double submit cookie approach to protect application against CSRF attacks
 * On kernel.controller if it controller or action is marked for CsrfProtection with annotation
 * listeners checks that value in cookie and value in request match. Otherwise access to controller is prohibited.
 */
class CsrfProtectionRequestListener
{
    private CsrfRequestManager $csrfRequestManager;

    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfRequestManager $csrfRequestManager, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfRequestManager = $csrfRequestManager;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Implements double submit cookie CSRF check.
     *
     * @throws AccessDeniedHttpException when route is protected against CSRF attacks and security check failed
     */
    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $this->csrfTokenManager->getToken(csrfRequestManager::CSRF_TOKEN_ID);

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

                $this->csrfRequestManager->refreshRequestToken();
            }
        }
    }

    /**
     * Regenerates CSRF cookie on each response.
     */
    public function onKernelResponse(ResponseEvent $event): void
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
