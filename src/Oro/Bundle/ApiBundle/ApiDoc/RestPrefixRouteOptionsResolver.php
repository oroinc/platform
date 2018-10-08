<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Resolves "oro_api.rest.prefix" DIC parameter in route path and "override_path" option.
 */
class RestPrefixRouteOptionsResolver implements RouteOptionsResolverInterface
{
    private const PREFIX = '%' . OroApiExtension::REST_API_PREFIX_PARAMETER_NAME . '%';

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
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

    /**
     * @param string $value
     *
     * @return bool
     */
    private function hasPrefix(string $value): bool
    {
        return false !== \strpos($value, self::PREFIX);
    }

    /**
     * Replaces %parameter% with it's value.
     *
     * @param string $value
     *
     * @return string
     */
    private function resolvePrefix(string $value): string
    {
        return \str_replace(
            self::PREFIX,
            $this->container->getParameter(OroApiExtension::REST_API_PREFIX_PARAMETER_NAME),
            $value
        );
    }
}
