<?php

namespace Oro\Bundle\SoapBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Reverts https://github.com/symfony/symfony/pull/28565 for REST API sub-requests to avoid BC break.
 */
class ApiSubRequestListener
{
    /** @var array [[request matcher, options], ...] */
    private array $rules;

    public function addRule(RequestMatcherInterface $requestMatcher, array $options = []): void
    {
        $this->rules[] = [$requestMatcher, $options];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->getRequestFormat(null)) {
            return;
        }

        if ($request->attributes->get('exception')) {
            // keep the request format unchanged for exception handling sub-request
            return;
        }

        /** @var RequestMatcherInterface $requestMatcher */
        foreach ($this->rules as list($requestMatcher, $options)) {
            if (!($options['stop'] ?? false) && $requestMatcher->matches($request)) {
                $request->setRequestFormat(null);
                break;
            }
        }
    }
}
