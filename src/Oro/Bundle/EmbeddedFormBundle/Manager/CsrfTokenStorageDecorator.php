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
    protected $mainTokenStorage;

    /** @var TokenStorageInterface */
    protected $embeddedFormTokenStorage;

    /** @var RequestStack */
    protected $requestStack;

    /** @var array */
    protected $sessionOptions;

    /** @var string */
    protected $embeddedFormRouteName;

    /** @var string */
    protected $sessionIdFieldName;

    /**
     * @param TokenStorageInterface $mainTokenStorage
     * @param TokenStorageInterface $embeddedFormTokenStorage
     * @param RequestStack          $requestStack
     * @param array                 $sessionOptions
     * @param string                $embeddedFormRouteName
     * @param string                $sessionIdFieldName
     */
    public function __construct(
        TokenStorageInterface $mainTokenStorage,
        TokenStorageInterface $embeddedFormTokenStorage,
        RequestStack $requestStack,
        array $sessionOptions,
        $embeddedFormRouteName,
        $sessionIdFieldName
    ) {
        $this->mainTokenStorage = $mainTokenStorage;
        $this->embeddedFormTokenStorage = $embeddedFormTokenStorage;
        $this->requestStack = $requestStack;
        $this->sessionOptions = $sessionOptions;
        $this->embeddedFormRouteName = $embeddedFormRouteName;
        $this->sessionIdFieldName = $sessionIdFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken($tokenId)
    {
        return $this->getTokenStorage()->hasToken($tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId)
    {
        return $this->getTokenStorage()->getToken($tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function setToken($tokenId, $token)
    {
        $this->getTokenStorage()->setToken($tokenId, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken($tokenId)
    {
        return $this->getTokenStorage()->removeToken($tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->getTokenStorage()->clear();
    }

    /**
     * @return TokenStorageInterface|ClearableTokenStorageInterface
     */
    protected function getTokenStorage()
    {
        $request = $this->requestStack->getMasterRequest();
        $isEmbeddedFormRequest =
            null !== $request
            && !$request->cookies->has($this->sessionOptions['name'])
            && $request->attributes->get('_route') === $this->embeddedFormRouteName;

        return $isEmbeddedFormRequest
            ? $this->embeddedFormTokenStorage
            : $this->mainTokenStorage;
    }
}
