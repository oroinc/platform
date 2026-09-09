<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Stores short-lived OAuth credentials in the authenticated user's session.
 */
class OAuthTokenStorage
{
    private const SESSION_KEY = '_oro_imap_oauth_credentials';
    private const LIFETIME = 900;

    public function __construct(
        private RequestStack $requestStack,
        private TokenAccessorInterface $tokenAccessor
    ) {
    }

    public function saveAccessTokenAndReturnHandleCode(
        string $accountType,
        string $accessToken,
        ?string $refreshToken,
        ?int $expiresIn
    ): string {
        $user = $this->tokenAccessor->getUser();
        if (null === $user || null === $user->getId()) {
            throw new \LogicException('An authenticated user is required to store OAuth credentials.');
        }

        $organization = $this->tokenAccessor->getOrganization() ?? $user->getOrganization();
        if (null === $organization || null === $organization->getId()) {
            throw new \LogicException('An organization is required to store OAuth credentials.');
        }

        $credentials = $this->getCredentials();
        $this->removeExpiredCredentials($credentials);

        $handle = bin2hex(random_bytes(32));
        $credentials[$handle] = [
            'accountType' => $accountType,
            'userId' => $user->getId(),
            'organizationId' => $organization->getId(),
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'expiresIn' => $expiresIn,
            'validUntil' => time() + self::LIFETIME
        ];

        $this->requestStack->getSession()->set(self::SESSION_KEY, $credentials);

        return $handle;
    }

    public function applyToOrigin(string $handle, string $accountType, UserEmailOrigin $origin): bool
    {
        $credential = $this->getCredential($handle, $accountType);
        if (null === $credential) {
            return false;
        }

        $origin->setAccessToken($credential['accessToken']);
        if (!empty($credential['refreshToken'])) {
            $origin->setRefreshToken($credential['refreshToken']);
        }

        $expiresIn = $credential['expiresIn'];
        $origin->setAccessTokenExpiresAt(
            null === $expiresIn
                ? null
                : new \DateTime('+' . (int)$expiresIn . ' seconds', new \DateTimeZone('UTC'))
        );

        return true;
    }

    private function getCredential(string $handle, string $accountType): ?array
    {
        $credentials = $this->getCredentials();
        $this->removeExpiredCredentials($credentials);
        $this->requestStack->getSession()->set(self::SESSION_KEY, $credentials);

        $credential = $credentials[$handle] ?? null;
        if (null === $credential || $credential['accountType'] !== $accountType) {
            return null;
        }

        $user = $this->tokenAccessor->getUser();
        $organization = $this->tokenAccessor->getOrganization() ?? $user?->getOrganization();
        if (
            null === $user
            || null === $organization
            || $credential['userId'] !== $user->getId()
            || $credential['organizationId'] !== $organization->getId()
        ) {
            return null;
        }

        return $credential;
    }

    private function getCredentials(): array
    {
        return (array) $this->requestStack->getSession()->get(self::SESSION_KEY, []);
    }

    private function removeExpiredCredentials(array &$credentials): void
    {
        $now = time();
        foreach ($credentials as $handle => $credential) {
            if (($credential['validUntil'] ?? 0) < $now) {
                unset($credentials[$handle]);
            }
        }
    }
}
