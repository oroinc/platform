<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Throws Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the resource related to this route is disabled
 */
class RequestListener
{
    protected FeatureChecker $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    public function onRequest(RequestEvent $event): void
    {
        $route = $event->getRequest()->get('_route');
        if (null !== $route && !$this->featureChecker->isResourceEnabled($route, 'routes')) {
            if ($event->isMasterRequest()) {
                throw new NotFoundHttpException();
            }

            $event->setResponse(new Response());
        }
    }
}
