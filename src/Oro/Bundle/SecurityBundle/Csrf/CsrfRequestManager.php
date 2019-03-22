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
    const CSRF_TOKEN_ID = '_csrf';
    const CSRF_HEADER = 'X-CSRF-Header';

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var string
     */
    private $tokenId;

    /**
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param string $tokenId
     */
    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        $tokenId = self::CSRF_TOKEN_ID
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->tokenId = $tokenId;
    }

    /**
     * Check that request is not a CSRF attack
     *
     * @param Request $request
     * @param bool $useRequestValue
     * @return bool
     */
    public function isRequestTokenValid(Request $request, $useRequestValue = false)
    {
        if ($useRequestValue) {
            $tokenValue = $request->get($this->tokenId);
        } else {
            $tokenValue = $request->headers->get(self::CSRF_HEADER);
        }

        return $this->csrfTokenManager->isTokenValid(new CsrfToken($this->tokenId, $tokenValue));
    }

    /**
     * Refresh CSRF token value
     */
    public function refreshRequestToken()
    {
        $this->csrfTokenManager->refreshToken($this->tokenId);
    }
}
