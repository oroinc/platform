<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * Stores CSRF tokens for embedded forms.
 */
class CsrfTokenStorage implements TokenStorageInterface, ClearableTokenStorageInterface
{
    /** @var AdapterInterface */
    protected $tokenCache;

    /** @var int */
    protected $tokenLifetime;

    /** @var SessionIdProviderInterface */
    protected $sessionIdProvider;

    /**
     * @param AdapterInterface $tokenCache
     * @param int $tokenLifetime
     * @param SessionIdProviderInterface $sessionIdProvider
     */
    public function __construct(
        AdapterInterface $tokenCache,
        $tokenLifetime,
        SessionIdProviderInterface $sessionIdProvider
    ) {
        $this->tokenCache = $tokenCache;
        $this->tokenLifetime = $tokenLifetime;
        $this->sessionIdProvider = $sessionIdProvider;
    }

    #[\Override]
    public function hasToken($tokenId): bool
    {
        return $this->tokenCache->hasItem($this->getCacheKey($tokenId));
    }

    #[\Override]
    public function getToken($tokenId): string
    {
        $cacheItem = $this->tokenCache->getItem($this->getCacheKey($tokenId));
        $token = $cacheItem->get();
        if (null === $token) {
            throw new TokenNotFoundException('The CSRF token with ID ' . $tokenId . ' does not exist.');
        }

        return $token;
    }

    #[\Override]
    public function setToken($tokenId, $token): void
    {
        $cacheItem = $this->tokenCache->getItem($this->getCacheKey($tokenId));
        $cacheItem
            ->set($token)
            ->expiresAfter($this->tokenLifetime);

        $this->tokenCache->save($cacheItem);

        if ($this->tokenCache instanceof PruneableInterface) {
            $this->tokenCache->prune();
        }
    }

    #[\Override]
    public function removeToken($tokenId): ?string
    {
        try {
            $token = $this->getToken($tokenId);
            $this->tokenCache->delete($this->getCacheKey($tokenId));
            return $token;
        } catch (TokenNotFoundException $e) {
            return null;
        }
    }

    #[\Override]
    public function clear(): void
    {
        $this->tokenCache->clear();
    }

    /**
     * @param string $tokenId
     *
     * @return string
     */
    protected function getCacheKey($tokenId)
    {
        return $tokenId . $this->sessionIdProvider->getSessionId();
    }
}
