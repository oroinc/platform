<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\EventListener;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds a UUID parameter to the request if it is not already present.
 * Redirects to the same URL with the generated UUID if the parameter is missing.
 */
class BeginDraftSessionOnRequestListener
{
    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param string $parameterName
     * @param array<string> $applicableRouteNames
     */
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $parameterName,
        private readonly array $applicableRouteNames,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethodSafe()) {
            return;
        }

        // Check if draftSessionUuid route parameter is empty
        $draftSessionUuid = $request->attributes->get($this->parameterName);
        if ($draftSessionUuid && is_string($draftSessionUuid)) {
            // UUID already present, nothing to do
            return;
        }

        $routeName = $request->attributes->get('_route');
        if (!is_string($routeName)) {
            return;
        }

        if (!in_array($routeName, $this->applicableRouteNames, true)) {
            return;
        }

        $routeParams = $request->attributes->get('_route_params', []);

        // Generate UUID and redirect to the same URL with the generated UUID
        $routeParams[$this->parameterName] = UUIDGenerator::v4();

        $url = $this->urlGenerator->generate($routeName, $routeParams + $request->query->all());
        $response = new RedirectResponse($url);
        $event->setResponse($response);
    }
}
