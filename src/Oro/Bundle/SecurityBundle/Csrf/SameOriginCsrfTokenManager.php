<?php

namespace Oro\Bundle\SecurityBundle\Csrf;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * This file is a copy of {@see Symfony\Component\Security\Csrf\SameOriginCsrfTokenManager} from Symfony 7.2
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class SameOriginCsrfTokenManager implements CsrfTokenManagerInterface
{
    public const TOKEN_MIN_LENGTH = 24;

    public const CHECK_NO_HEADER = 0;
    public const CHECK_HEADER = 1;
    public const CHECK_ONLY_HEADER = 2;

    /**
     * @param self::CHECK_* $checkHeader
     * @param string[] $tokenIds
     */
    public function __construct(
        private RequestStack $requestStack,
        private ?LoggerInterface $logger = null,
        private ?CsrfTokenManagerInterface $fallbackCsrfTokenManager = null,
        private array $tokenIds = [],
        private int $checkHeader = self::CHECK_NO_HEADER,
        private string $cookieName = 'csrf-token',
    ) {
        if (!$cookieName) {
            throw new \InvalidArgumentException('The cookie name cannot be empty.');
        }

        if (!preg_match('/^[-a-zA-Z0-9_]+$/D', $cookieName)) {
            throw new \InvalidArgumentException('The cookie name contains invalid characters.');
        }

        $this->tokenIds = array_flip($tokenIds);
    }

    public function getToken(string $tokenId): CsrfToken
    {
        if (!isset($this->tokenIds[$tokenId]) && $this->fallbackCsrfTokenManager) {
            return $this->fallbackCsrfTokenManager->getToken($tokenId);
        }

        return new CsrfToken($tokenId, $this->cookieName);
    }

    public function refreshToken(string $tokenId): CsrfToken
    {
        if (!isset($this->tokenIds[$tokenId]) && $this->fallbackCsrfTokenManager) {
            return $this->fallbackCsrfTokenManager->refreshToken($tokenId);
        }

        return new CsrfToken($tokenId, $this->cookieName);
    }

    public function removeToken(string $tokenId): ?string
    {
        if (!isset($this->tokenIds[$tokenId]) && $this->fallbackCsrfTokenManager) {
            return $this->fallbackCsrfTokenManager->removeToken($tokenId);
        }

        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function isTokenValid(CsrfToken $token): bool
    {
        if (!isset($this->tokenIds[$token->getId()]) && $this->fallbackCsrfTokenManager) {
            return $this->fallbackCsrfTokenManager->isTokenValid($token);
        }

        if (!$request = $this->requestStack->getCurrentRequest()) {
            $this->logger?->error('CSRF validation failed: No request found.');

            return false;
        }

        if (\strlen($token->getValue()) < self::TOKEN_MIN_LENGTH && $token->getValue() !== $this->cookieName) {
            $this->logger?->warning('Invalid double-submit CSRF token.');

            return false;
        }

        if (false === $isValidOrigin = $this->isValidOrigin($request)) {
            $this->logger?->warning('CSRF validation failed: origin info doesn\'t match.');

            return false;
        }

        if (false === $isValidDoubleSubmit = $this->isValidDoubleSubmit($request, $token->getValue())) {
            return false;
        }

        if (null === $isValidOrigin && null === $isValidDoubleSubmit) {
            $this->logger?->warning('CSRF validation failed: double-submit and origin info not found.');

            return false;
        }

        // Opportunistically lookup at the session for a previous CSRF validation strategy
        $session = $request->hasPreviousSession() ? $request->getSession() : null;
        $usageIndexValue = $session instanceof Session ? $usageIndexReference = &$session->getUsageIndex() : 0;
        $usageIndexReference = \PHP_INT_MIN;
        $previousCsrfProtection = (int)$session?->get($this->cookieName);
        $usageIndexReference = $usageIndexValue;
        $shift = $request->isMethodSafe() ? 8 : 0;

        if ($previousCsrfProtection) {
            if (!$isValidOrigin && (1 & ($previousCsrfProtection >> $shift))) {
                $this->logger?->warning(
                    'CSRF validation failed: origin info was used in a previous request but is now missing.'
                );

                return false;
            }

            if (!$isValidDoubleSubmit && (2 & ($previousCsrfProtection >> $shift))) {
                $this->logger?->warning(
                    'CSRF validation failed: double-submit info was used in a previous request but is now missing.'
                );

                return false;
            }
        }

        if ($isValidOrigin && $isValidDoubleSubmit) {
            $csrfProtection = 3;
            $this->logger?->debug('CSRF validation accepted using both origin and double-submit info.');
        } elseif ($isValidOrigin) {
            $csrfProtection = 1;
            $this->logger?->debug('CSRF validation accepted using origin info.');
        } else {
            $csrfProtection = 2;
            $this->logger?->debug('CSRF validation accepted using double-submit info.');
        }

        if (1 & $csrfProtection) {
            // Persist valid origin for both safe and non-safe requests
            $previousCsrfProtection |= 1 & (1 << 8);
        }

        $request->attributes->set($this->cookieName, ($csrfProtection << $shift) | $previousCsrfProtection);

        return true;
    }

    public function clearCookies(Request $request, Response $response): void
    {
        if (!$request->attributes->has($this->cookieName)) {
            return;
        }

        $cookieName = ($request->isSecure() ? '__Host-' : '') . $this->cookieName;

        foreach ($request->cookies->all() as $name => $value) {
            if ($this->cookieName === $value && str_starts_with($name, $cookieName . '_')) {
                $response->headers->clearCookie($name, '/', null, $request->isSecure(), false, 'strict');
            }
        }
    }

    public function persistStrategy(Request $request): void
    {
        if ($request->hasSession(true) && $request->attributes->has($this->cookieName)) {
            $request->getSession()->set($this->cookieName, $request->attributes->get($this->cookieName));
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->clearCookies($event->getRequest(), $event->getResponse());
        $this->persistStrategy($event->getRequest());
    }

    /**
     * @return bool|null Whether the origin is valid, null if missing
     */
    private function isValidOrigin(Request $request): ?bool
    {
        $source = $request->headers->get('Origin') ?? $request->headers->get('Referer') ?? 'null';

        return 'null' === $source ? null : str_starts_with($source . '/', $request->getSchemeAndHttpHost() . '/');
    }

    /**
     * @return bool|null Whether the double-submit is valid, null if missing
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isValidDoubleSubmit(Request $request, string $token): ?bool
    {
        if ($this->cookieName === $token) {
            return null;
        }

        if ($this->checkHeader && $request->headers->get($this->cookieName, $token) !== $token) {
            $this->logger?->warning('CSRF validation failed: wrong token found in header info.');

            return false;
        }

        $cookieName = ($request->isSecure() ? '__Host-' : '') . $this->cookieName;

        if (self::CHECK_ONLY_HEADER === $this->checkHeader) {
            if (!$request->headers->has($this->cookieName)) {
                return null;
            }

            $request->cookies->set(
                $cookieName . '_' . $token,
                $this->cookieName
            ); // Ensure clearCookie() can remove any cookie filtered by a reverse-proxy

            return true;
        }

        if (($request->cookies->all()[$cookieName . '_' . $token] ?? null) !== $this->cookieName &&
            !($this->checkHeader && $request->headers->has($this->cookieName))) {
            return null;
        }

        return true;
    }
}
