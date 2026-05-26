<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\EventListener;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Adds a UUID parameter to the RequestContext if it is not already present.
 * Sets the UUID from the request if any.
 * Allows to configure the request context parameter name via constructor.
 */
final class SetDraftSessionUuidToRequestContextListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private readonly ApplicationState $applicationState,
        private readonly RequestContextAwareInterface $router,
        private readonly string $parameterName
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if (!$this->applicationState->isInstalled()) {
            return;
        }

        $requestContext = $this->router->getContext();
        if ($requestContext->getParameter($this->parameterName)) {
            return;
        }

        $request = $event->getRequest();
        $uuid = $request->get($this->parameterName);
        if ($uuid) {
            $requestContext->setParameter($this->parameterName, $uuid);
        }
    }
}
