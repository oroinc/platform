<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides entity index and view (as well as update and create, if defined) URLs.
 */
class EntityUrlProvider extends AbstractEntityUrlProvider
{
    public function __construct(
        RouterInterface $router,
        protected readonly ConfigManager $configManager,
    ) {
        $this->router = $router;
    }

    public function getRoute(
        object|string $entity,
        string $routeType = self::ROUTE_INDEX,
        bool $throwExceptionIfNotDefined = false
    ): ?string {
        $entityFQCN = \is_object($entity) ? ClassUtils::getClass($entity) : $entity;

        if (!$this->configManager->hasConfig($entityFQCN)) {
            return null;
        }

        $entityMetadata = $this->configManager->getEntityMetadata($entityFQCN);
        if (!$entityMetadata) {
            return null;
        }

        // EntityMetadata uses 'routeName' instead of 'routeIndex' for index routes
        $metadataRouteType = $routeType === self::ROUTE_INDEX ? 'name' : $routeType;

        $route = $entityMetadata->getRoute($metadataRouteType, $throwExceptionIfNotDefined);

        return $route && $this->routerHasRoute($route)
            ? $route
            : null;
    }
}
