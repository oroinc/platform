<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class RequestListener
{
    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        $route = $event->getRequest()->get('_route');
        if (!$this->featureChecker->isResourceEnabled($route, 'routes')) {
            if ($event->isMasterRequest()) {
                throw new NotFoundHttpException();
            }

            $event->setResponse(new Response());
        }
    }
}
