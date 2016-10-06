<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * This decorator allows to use the embedded form even if third-party cookies
 * are blocked in a web browser.
 */
class CsrfTokenStorageDecorator implements TokenStorageInterface
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
    protected $embeddedFormUrlPrefix;

    /** @var string */
    protected $sessionIdFieldName;

    /**
     * @param TokenStorageInterface $mainTokenStorage
     * @param TokenStorageInterface $embeddedFormTokenStorage
     * @param RequestStack          $requestStack
     * @param array                 $sessionOptions
     * @param string                $embeddedFormUrlPrefix
     * @param string                $sessionIdFieldName
     */
    public function __construct(
        TokenStorageInterface $mainTokenStorage,
        TokenStorageInterface $embeddedFormTokenStorage,
        RequestStack $requestStack,
        array $sessionOptions,
        $embeddedFormUrlPrefix,
        $sessionIdFieldName
    ) {
        $this->mainTokenStorage = $mainTokenStorage;
        $this->embeddedFormTokenStorage = $embeddedFormTokenStorage;
        $this->requestStack = $requestStack;
        $this->sessionOptions = $sessionOptions;
        $this->embeddedFormUrlPrefix = $embeddedFormUrlPrefix;
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
     * @return TokenStorageInterface
     */
    protected function getTokenStorage()
    {
        $isEmbeddedFormRequest = false;
        $request = $this->requestStack->getMasterRequest();
        if (null !== $request) {
            $isEmbeddedFormRequest =
                null !== $request
                && !$request->cookies->has($this->sessionOptions['name'])
                && 0 === strpos($request->server->get('PATH_INFO'), $this->embeddedFormUrlPrefix);
        }

        return $isEmbeddedFormRequest
            ? $this->embeddedFormTokenStorage
            : $this->mainTokenStorage;
    }
}
