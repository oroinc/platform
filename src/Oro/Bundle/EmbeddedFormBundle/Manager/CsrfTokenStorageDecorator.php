<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * This decorator allows to use the embedded form even if third-party cookies
 * are blocked in a web browser.
 */
class CsrfTokenStorageDecorator implements TokenStorageInterface, ClearableTokenStorageInterface
{
    /** @var TokenStorageInterface */
    private $mainTokenStorage;

    /** @var TokenStorageInterface */
    private $embeddedFormTokenStorage;

    /** @var RequestStack */
    private $requestStack;

    /** @var string */
    private $embeddedFormRouteName;

    /**
     * @param TokenStorageInterface $mainTokenStorage
     * @param TokenStorageInterface $embeddedFormTokenStorage
     * @param RequestStack          $requestStack
     * @param string                $embeddedFormRouteName
     */
    public function __construct(
        TokenStorageInterface $mainTokenStorage,
        TokenStorageInterface $embeddedFormTokenStorage,
        RequestStack $requestStack,
        $embeddedFormRouteName
    ) {
        $this->mainTokenStorage = $mainTokenStorage;
        $this->embeddedFormTokenStorage = $embeddedFormTokenStorage;
        $this->requestStack = $requestStack;
        $this->embeddedFormRouteName = $embeddedFormRouteName;
    }

    #[\Override]
    public function hasToken($tokenId): bool
    {
        return $this->getTokenStorage()->hasToken($tokenId);
    }

    #[\Override]
    public function getToken($tokenId): string
    {
        return $this->getTokenStorage()->getToken($tokenId);
    }

    #[\Override]
    public function setToken($tokenId, $token)
    {
        $this->getTokenStorage()->setToken($tokenId, $token);
    }

    #[\Override]
    public function removeToken($tokenId): ?string
    {
        return $this->getTokenStorage()->removeToken($tokenId);
    }

    #[\Override]
    public function clear()
    {
        return $this->getTokenStorage()->clear();
    }

    /**
     * @return TokenStorageInterface|ClearableTokenStorageInterface
     */
    private function getTokenStorage()
    {
        return $this->isEmbeddedFormRequest()
            ? $this->embeddedFormTokenStorage
            : $this->mainTokenStorage;
    }

    /**
     * @return bool
     */
    private function isEmbeddedFormRequest()
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || $request->attributes->get('_route') !== $this->embeddedFormRouteName) {
            return false;
        }

        $session = $request->hasSession() ? $request->getSession() : null;

        return $session && !$request->cookies->has($session->getName());
    }
}
