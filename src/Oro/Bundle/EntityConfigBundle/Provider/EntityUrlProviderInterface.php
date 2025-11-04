<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Provider;

/**
 * Interface for entity URL providers.
 */
interface EntityUrlProviderInterface
{
    public const string ROUTE_INDEX = 'index';
    public const string ROUTE_VIEW = 'view';
    public const string ROUTE_UPDATE = 'update';
    public const string ROUTE_CREATE = 'create';

    public function getIndexUrl(object|string $entity, array $extraRouteParams = []): ?string;

    public function getViewUrl(object|string $entity, int $entityId, array $extraRouteParams = []): ?string;

    public function getUpdateUrl(object|string $entity, int $entityId, array $extraRouteParams = []): ?string;

    public function getCreateUrl(object|string $entity, array $extraRouteParams = []): ?string;

    public function getRoute(
        object|string $entity,
        string $routeType = self::ROUTE_INDEX,
        bool $throwExceptionIfNotDefined = false
    ): ?string;
}
