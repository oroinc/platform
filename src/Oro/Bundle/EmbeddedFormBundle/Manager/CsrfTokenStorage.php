<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * Stores CSRF tokens for embedded forms.
 */
class CsrfTokenStorage implements TokenStorageInterface, ClearableTokenStorageInterface
{
    /** @var CacheProvider */
    protected $tokenCache;

    /** @var int */
    protected $tokenLifetime;

    /** @var SessionIdProviderInterface */
    protected $sessionIdProvider;

    /**
     * @param CacheProvider              $tokenCache
     * @param int                        $tokenLifetime
     * @param SessionIdProviderInterface $sessionIdProvider
     */
    public function __construct(
        CacheProvider $tokenCache,
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
        return false !== $this->tokenCache->fetch($this->getCacheKey($tokenId));
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId)
    {
        $token = $this->tokenCache->fetch($this->getCacheKey($tokenId));
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
        $this->tokenCache->save($this->getCacheKey($tokenId), $token, $this->tokenLifetime);
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
        $this->tokenCache->deleteAll();
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
