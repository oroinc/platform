<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * Stores CSRF tokens for embedded forms.
 */
class CsrfTokenStorage implements TokenStorageInterface, ClearableTokenStorageInterface
{
    /** @var CacheInterface */
    protected $tokenCache;

    /** @var int */
    protected $tokenLifetime;

    /** @var SessionIdProviderInterface */
    protected $sessionIdProvider;

    /**
     * @param CacheInterface $tokenCache
     * @param int $tokenLifetime
     * @param SessionIdProviderInterface $sessionIdProvider
     */
    public function __construct(
        CacheInterface $tokenCache,
        $tokenLifetime,
        SessionIdProviderInterface $sessionIdProvider
    ) {
        $this->tokenCache = $tokenCache;
        $this->tokenLifetime = $tokenLifetime;
        $this->sessionIdProvider = $sessionIdProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken($tokenId)
    {
        return $this->tokenCache->has($this->getCacheKey($tokenId));
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId)
    {
        $token = $this->tokenCache->get($this->getCacheKey($tokenId));
        if (false === $token) {
            $token = null;
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function setToken($tokenId, $token)
    {
        $this->tokenCache->set($this->getCacheKey($tokenId), $token, $this->tokenLifetime);

        if ($this->tokenCache instanceof PruneableInterface) {
            $this->tokenCache->prune();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken($tokenId)
    {
        $this->tokenCache->delete($this->getCacheKey($tokenId));
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
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
