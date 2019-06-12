<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;

/**
 * Removes auto-generated single item related API routes for the specified entity.
 * The list of API actions that are removed can be found in RestActionMapper::getActions.
 * @see \Oro\Bundle\ApiBundle\ApiDoc\RestActionMapper::getActions
 * This class can be useful when you need API resource for a list of entities,
 * but do not need API resource for a single entity.
 *
 * Example of usage:
 * <code>
 *  services:
 *      acme.api.rest.remove_some_single_item_route:
 *          parent: oro_api.rest.routing_options_resolver.remove_single_item_routes
 *          arguments:
 *              index_0: 'rest_api'
 *              index_1: 'Oro\Bundle\AcmeBundle\Api\Model\SomeModel'
 *          tags:
 *              - { name: oro.api.routing_options_resolver, view: rest_json_api, priority: -10 }
 *              - { name: oro.api.routing_options_resolver, view: rest_plain, priority: -10 }
 * </code>
 */
class RemoveSingleItemRestRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /** @var string */
    private $routeGroup;

    /** @var string */
    private $entityClass;

    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var RestRoutes */
    private $routes;

    /** @var RestActionMapper */
    private $actionMapper;

    /**
     * @param string              $routeGroup
     * @param string              $entityClass
     * @param RestDocViewDetector $docViewDetector
     * @param ValueNormalizer     $valueNormalizer
     * @param RestRoutes          $routes
     * @param RestActionMapper    $actionMapper
     */
    public function __construct(
        string $routeGroup,
        string $entityClass,
        RestDocViewDetector $docViewDetector,
        ValueNormalizer $valueNormalizer,
        RestRoutes $routes,
        RestActionMapper $actionMapper
    ) {
        $this->routeGroup = $routeGroup;
        $this->entityClass = $entityClass;
        $this->docViewDetector = $docViewDetector;
        $this->valueNormalizer = $valueNormalizer;
        $this->routes = $routes;
        $this->actionMapper = $actionMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (!$this->routeGroup) {
            throw new \LogicException('The route group must be specified.');
        }
        if (!$this->entityClass) {
            throw new \LogicException('The entity class must be specified.');
        }

        if ($route->getOption(RestRouteOptionsResolver::GROUP_OPTION) !== $this->routeGroup
            || $this->docViewDetector->getRequestType()->isEmpty()
        ) {
            return;
        }

        $routeName = $routes->getName($route);
        if ($this->routes->getItemRouteName() !== $routeName) {
            return;
        }

        $entityType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $this->entityClass,
            $this->docViewDetector->getRequestType()
        );
        $routePath = str_replace(
            RestRouteOptionsResolver::ENTITY_PLACEHOLDER,
            $entityType,
            $route->getPath()
        );
        $this->removeRoute($routes, $routePath, $this->actionMapper->getActions($routeName));
    }

    /**
     * @param RouteCollectionAccessor $routes
     * @param string                  $routePath
     * @param string[]                $actions
     */
    private function removeRoute(RouteCollectionAccessor $routes, string $routePath, array $actions): void
    {
        foreach ($actions as $action) {
            $route = $routes->getByPath($routePath, [$this->actionMapper->getMethod($action)]);
            if (null !== $route) {
                $routes->remove($routes->getName($route));
            }
        }
    }
}
