<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

use Oro\Bundle\ApiBundle\Request\ApiAction;

/**
 * Provides a set of static methods that help building names for OpenAPI models.
 */
final class ModelNameUtil
{
    private const MODEL_SUFFIX = 'Model';
    private const MODEL_COLLECTION_SUFFIX = 'CollectionModel';

    public static function buildModelName(
        string $prefix,
        ?string $action = null,
        bool $isCollection = false,
        ?string $associationName = null
    ): string {
        if ($associationName) {
            return
                $prefix
                . ($action && self::isRelationshipRelatedApiAction($action) ? 'RelationshipFor' : 'SubresourceFor')
                . ucfirst($associationName)
                . self::MODEL_SUFFIX;
        }

        return $prefix . ($isCollection ? self::MODEL_COLLECTION_SUFFIX : self::MODEL_SUFFIX);
    }

    public static function getModelNameWithoutSuffix(string $modelName): string
    {
        if (str_ends_with($modelName, self::MODEL_COLLECTION_SUFFIX)) {
            return substr($modelName, 0, -\strlen(self::MODEL_COLLECTION_SUFFIX));
        }
        if (str_ends_with($modelName, self::MODEL_SUFFIX)) {
            return substr($modelName, 0, -\strlen(self::MODEL_SUFFIX));
        }

        return $modelName;
    }

    private static function isRelationshipRelatedApiAction(string $action): bool
    {
        return
            ApiAction::GET_RELATIONSHIP === $action
            || ApiAction::ADD_RELATIONSHIP === $action
            || ApiAction::UPDATE_RELATIONSHIP === $action
            || ApiAction::DELETE_RELATIONSHIP === $action;
    }
}
