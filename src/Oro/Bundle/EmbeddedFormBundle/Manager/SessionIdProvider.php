<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;

class SessionIdProvider implements SessionIdProviderInterface
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var string */
    protected $sessionIdFieldName;

    /**
     * @param RequestStack $requestStack
     * @param string       $sessionIdFieldName
     */
    public function __construct(RequestStack $requestStack, $sessionIdFieldName)
    {
        $this->requestStack = $requestStack;
        $this->sessionIdFieldName = $sessionIdFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionId()
    {
        $sessionId = null;
        $request = $this->requestStack->getMasterRequest();
        if (null !== $request) {
            $method = $request->getMethod();
            if ('POST' === $method) {
                $sessionId = $request->request->get($this->sessionIdFieldName);
            } elseif ('GET' === $method) {
                if ($request->attributes->has($this->sessionIdFieldName)) {
                    $sessionId = $request->attributes->get($this->sessionIdFieldName);
                } else {
                    $sessionId = uniqid('', true);
                    $request->attributes->set($this->sessionIdFieldName, $sessionId);
                }
            }
        }

        return $sessionId;
    }
}
