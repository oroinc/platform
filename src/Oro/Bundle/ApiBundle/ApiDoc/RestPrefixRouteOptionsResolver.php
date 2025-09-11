<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;

/**
 * Resolves API prefix in route path and "override_path" option.
 */
class RestPrefixRouteOptionsResolver implements RouteOptionsResolverInterface
{
    public function __construct(
        private readonly string $apiPrefix,
        private readonly string $apiPrefixPlaceholder
    ) {
    }

    #[\Override]
    public function resolve(Route $route, RouteCollectionAccessor $routes): void
    {
        $path = $route->getPath();
        if ($path && $this->hasPrefix($path)) {
            $route->setPath($this->resolvePrefix($path));
        }
        $overridePath = $route->getOption(RestRouteOptionsResolver::OVERRIDE_PATH_OPTION);
        if ($overridePath && $this->hasPrefix($overridePath)) {
            $route->setOption(RestRouteOptionsResolver::OVERRIDE_PATH_OPTION, $this->resolvePrefix($overridePath));
        }
    }

    private function hasPrefix(string $value): bool
    {
        return str_contains($value, $this->apiPrefixPlaceholder);
    }

    private function resolvePrefix(string $value): string
    {
        return str_replace($this->apiPrefixPlaceholder, $this->apiPrefix, $value);
    }
}
