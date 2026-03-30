<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Routing;

use Oro\Bundle\DistributionBundle\Event\RouterGenerateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Decorates a router to extend the route generation functionality with:
 * - the ability to modify the route name, parameters, and reference type via the RouterGenerateEvent event.
 */
class Router implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        private readonly RouterInterface&RequestMatcherInterface $innerRouter
    ) {
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public function setContext(RequestContext $context): void
    {
        $this->innerRouter->setContext($context);
    }

    #[\Override]
    public function getContext(): RequestContext
    {
        return $this->innerRouter->getContext();
    }

    #[\Override]
    public function matchRequest(Request $request): array
    {
        return $this->innerRouter->matchRequest($request);
    }

    #[\Override]
    public function getRouteCollection(): RouteCollection
    {
        return $this->innerRouter->getRouteCollection();
    }

    #[\Override]
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        $event = new RouterGenerateEvent($name, $parameters, $referenceType);
        $this->eventDispatcher?->dispatch($event);

        return $this->innerRouter->generate(
            $event->getRouteName(),
            $event->getParameters(),
            $event->getReferenceType()
        );
    }

    #[\Override]
    public function match(string $pathinfo): array
    {
        return $this->innerRouter->match($pathinfo);
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        if ($this->innerRouter instanceof WarmableInterface) {
            return $this->innerRouter->warmUp($cacheDir);
        }

        return [];
    }
}
