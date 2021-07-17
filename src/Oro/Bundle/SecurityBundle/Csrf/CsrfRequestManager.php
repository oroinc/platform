<?php

namespace Oro\Bundle\SecurityBundle\Csrf;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * CSRF request manager is responsible for checking that request has valid CSRF protection token.
 */
class CsrfRequestManager
{
    public const CSRF_TOKEN_ID = '_csrf';
    public const CSRF_HEADER   = 'X-CSRF-Header';

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Checks that the given request is not a CSRF attack.
     */
    public function isRequestTokenValid(Request $request, bool $useRequestValue = false): bool
    {
        $tokenValue = $useRequestValue
            ? $request->get(self::CSRF_TOKEN_ID)
            : $request->headers->get(self::CSRF_HEADER);
        if (!$tokenValue) {
            return false;
        }

        return $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $tokenValue));
    }

    /**
     * Generates a new token value for the CSRF token.
     */
    public function refreshRequestToken(): void
    {
        $this->csrfTokenManager->refreshToken(self::CSRF_TOKEN_ID);
    }
}
