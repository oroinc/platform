<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Abstract implementation of EntityUrlProviderInterface.
 */
abstract class AbstractEntityUrlProvider implements EntityUrlProviderInterface
{
    protected RouterInterface $router;

    public function getIndexUrl(object|string $entity, array $extraRouteParams = []): ?string
    {
        return $this->getUrl($entity, self::ROUTE_INDEX, $extraRouteParams);
    }

    public function getViewUrl(object|string $entity, int $entityId, array $extraRouteParams = []): ?string
    {
        return $this->getUrl($entity, self::ROUTE_VIEW, \array_merge($extraRouteParams, ['id' => $entityId]));
    }

    public function getUpdateUrl(object|string $entity, int $entityId, array $extraRouteParams = []): ?string
    {
        return $this->getUrl($entity, self::ROUTE_UPDATE, \array_merge($extraRouteParams, ['id' => $entityId]));
    }

    public function getCreateUrl(object|string $entity, array $extraRouteParams = []): ?string
    {
        return $this->getUrl($entity, self::ROUTE_CREATE, $extraRouteParams);
    }

    abstract public function getRoute(
        object|string $entity,
        string $routeType = self::ROUTE_INDEX,
        bool $throwExceptionIfNotDefined = false
    ): ?string;

    protected function getUrl(
        object|string $entity,
        string $routeType = self::ROUTE_INDEX,
        array $extraRouteParams = []
    ): ?string {
        $route = $this->getRoute($entity, $routeType);
        if ($route) {
            return $this->router->generate($route, $extraRouteParams);
        }

        return null;
    }

    protected function routerHasRoute(string $routeName): bool
    {
        try {
            $this->router->generate($routeName);
        } catch (RouteNotFoundException $e) {
            return false;
        } catch (RoutingException $e) {
            return true;
        }

        return true;
    }
}
