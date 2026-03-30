<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouterGenerateEvent;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Sets UUID parameter from request context when an applicable route is being generated.
 */
final class KeepDraftSessionUuidOnRouteGenerateListener
{
    /**
     * @param RequestContextAwareInterface $router
     * @param string $parameterName
     * @param array<string> $applicableRouteNames
     */
    public function __construct(
        private readonly RequestContextAwareInterface $router,
        private readonly string $parameterName,
        private readonly array $applicableRouteNames
    ) {
    }

    /**
     * Sets a UUID parameter to the applicable route if it is not set.
     * Takes a UUID from the RequestContext.
     */
    public function onRouterGenerate(RouterGenerateEvent $event): void
    {
        if (!in_array($event->getRouteName(), $this->applicableRouteNames, true)) {
            return;
        }

        if ($event->hasParameter($this->parameterName)) {
            return;
        }

        $requestContext = $this->router->getContext();
        if (!$requestContext->getParameter($this->parameterName)) {
            return;
        }

        $event->setParameter($this->parameterName, $requestContext->getParameter($this->parameterName));
    }
}
