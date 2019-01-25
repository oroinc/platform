<?php

namespace Oro\Bundle\SoapBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Reverts https://github.com/symfony/symfony/pull/28565 for REST API sub-requests to avoid BC break.
 */
class ApiSubRequestListener
{
    /** @var array [[request matcher, options], ...] */
    private $rules;

    /**
     * @param RequestMatcherInterface $requestMatcher
     * @param array                   $options
     */
    public function addRule(RequestMatcherInterface $requestMatcher, array $options = []): void
    {
        $this->rules[] = [$requestMatcher, $options];
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($event->isMasterRequest() || !$request->getRequestFormat(null)) {
            return;
        }

        /** @var RequestMatcherInterface $requestMatcher */
        foreach ($this->rules as list($requestMatcher, $options)) {
            if ((!isset($options['stop']) || !$options['stop']) && $requestMatcher->matches($request)) {
                $request->setRequestFormat(null);
                break;
            }
        }
    }
}
