<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;

/**
 * Provides a set of reusable static methods that can be useful to load sub-resources.
 */
class SubresourceUtil
{
    public const SUBRESOURCE_DEFAULT_EXCLUDED_ACTIONS = [
        ApiAction::UPDATE_SUBRESOURCE,
        ApiAction::ADD_SUBRESOURCE,
        ApiAction::DELETE_SUBRESOURCE
    ];

    public const SUBRESOURCE_ACTIONS = [
        ApiAction::GET_SUBRESOURCE,
        ApiAction::UPDATE_SUBRESOURCE,
        ApiAction::ADD_SUBRESOURCE,
        ApiAction::DELETE_SUBRESOURCE,
        ApiAction::GET_RELATIONSHIP,
        ApiAction::UPDATE_RELATIONSHIP,
        ApiAction::ADD_RELATIONSHIP,
        ApiAction::DELETE_RELATIONSHIP
    ];

    public const SUBRESOURCE_ACTIONS_WITHOUT_GET_SUBRESOURCE = [
        ApiAction::UPDATE_SUBRESOURCE,
        ApiAction::ADD_SUBRESOURCE,
        ApiAction::DELETE_SUBRESOURCE,
        ApiAction::GET_RELATIONSHIP,
        ApiAction::UPDATE_RELATIONSHIP,
        ApiAction::ADD_RELATIONSHIP,
        ApiAction::DELETE_RELATIONSHIP
    ];

    public const RELATIONSHIP_CHANGE_ACTIONS = [
        ApiAction::UPDATE_RELATIONSHIP,
        ApiAction::ADD_RELATIONSHIP,
        ApiAction::DELETE_RELATIONSHIP
    ];

    public static function isSubresourcesEnabled(ApiResource $resource): bool
    {
        return !\in_array(ApiAction::GET_SUBRESOURCE, $resource->getExcludedActions(), true);
    }

    public static function isAccessibleSubresource(ApiSubresource $subresource, array $accessibleResources): bool
    {
        $targetClassNames = $subresource->getAcceptableTargetClassNames();
        if (empty($targetClassNames)) {
            $targetClassName = $subresource->getTargetClassName();
            if (is_a($targetClassName, EntityIdentifier::class, true)) {
                return true;
            }

            return isset($accessibleResources[$targetClassName]);
        }

        foreach ($targetClassNames as $className) {
            if (isset($accessibleResources[$className])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ApiSubresource $subresource
     * @param string         $targetClassName
     * @param string[]       $acceptableTargetClassNames
     */
    public static function setAcceptableTargetClasses(
        ApiSubresource $subresource,
        string $targetClassName,
        array $acceptableTargetClassNames = []
    ): void {
        if (is_a($targetClassName, EntityIdentifier::class, true)) {
            $subresource->setAcceptableTargetClassNames($acceptableTargetClassNames);
        } elseif (!empty($acceptableTargetClassNames)
            && (
                \count($acceptableTargetClassNames) > 1
                || reset($acceptableTargetClassNames) !== $targetClassName
            )
        ) {
            foreach ($acceptableTargetClassNames as $className) {
                if (!is_a($className, $targetClassName, true)) {
                    throw new \RuntimeException(sprintf(
                        'The acceptable target class "%s" should be "%s" or its subclass.',
                        $className,
                        $targetClassName
                    ));
                }
            }
            $subresource->setAcceptableTargetClassNames($acceptableTargetClassNames);
        }
    }

    public static function setSubresourceExcludedActions(
        ApiSubresource $subresource,
        array $accessibleResources,
        array $subresourceExcludedActions = null
    ): void {
        if (self::isAccessibleSubresource($subresource, $accessibleResources)) {
            if ($subresourceExcludedActions) {
                $subresource->setExcludedActions($subresourceExcludedActions);
            }
            if (!$subresource->isCollection()) {
                self::ensureActionExcluded($subresource, ApiAction::ADD_RELATIONSHIP);
                self::ensureActionExcluded($subresource, ApiAction::DELETE_RELATIONSHIP);
            }
        } else {
            $subresource->setExcludedActions(self::SUBRESOURCE_ACTIONS);
        }
    }

    public static function ensureActionExcluded(ApiSubresource $subresource, string $action): void
    {
        if (!$subresource->isExcludedAction($action)) {
            $subresource->addExcludedAction($action);
        }
    }
}
