<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Stub;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouterStub implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    public function __construct(
        private readonly RouterInterface&RequestMatcherInterface $innerRouter
    ) {
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
        return $this->innerRouter->generate($name, $parameters, $referenceType);
    }

    #[\Override]
    public function match(string $pathinfo): array
    {
        return $this->innerRouter->match($pathinfo);
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        return $this->innerRouter->warmup($cacheDir);
    }
}
