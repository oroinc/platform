<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Resolves API prefix in route path and "override_path" option.
 */
class RestPrefixRouteOptionsResolver implements RouteOptionsResolverInterface
{
    private ContainerInterface $container;
    private string $apiPrefix;
    private string $apiPrefixPlaceholder;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->setApiPrefixParameterName(OroApiExtension::REST_API_PREFIX_PARAMETER_NAME);
    }

    public function setApiPrefixParameterName(string $apiPrefixParameterName)
    {
        $this->apiPrefix = $this->container->getParameter($apiPrefixParameterName);
        $this->apiPrefixPlaceholder = '%' . $apiPrefixParameterName . '%';
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
