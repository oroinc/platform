<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;

/**
 * Provides a set of reusable static methods that can be useful to load sub-resources.
 */
class SubresourceUtil
{
    public const SUBRESOURCE_DEFAULT_EXCLUDED_ACTIONS = [
        ApiActions::UPDATE_SUBRESOURCE,
        ApiActions::ADD_SUBRESOURCE,
        ApiActions::DELETE_SUBRESOURCE
    ];

    public const SUBRESOURCE_ACTIONS = [
        ApiActions::GET_SUBRESOURCE,
        ApiActions::UPDATE_SUBRESOURCE,
        ApiActions::ADD_SUBRESOURCE,
        ApiActions::DELETE_SUBRESOURCE,
        ApiActions::GET_RELATIONSHIP,
        ApiActions::UPDATE_RELATIONSHIP,
        ApiActions::ADD_RELATIONSHIP,
        ApiActions::DELETE_RELATIONSHIP
    ];

    public const SUBRESOURCE_ACTIONS_WITHOUT_GET_SUBRESOURCE = [
        ApiActions::UPDATE_SUBRESOURCE,
        ApiActions::ADD_SUBRESOURCE,
        ApiActions::DELETE_SUBRESOURCE,
        ApiActions::GET_RELATIONSHIP,
        ApiActions::UPDATE_RELATIONSHIP,
        ApiActions::ADD_RELATIONSHIP,
        ApiActions::DELETE_RELATIONSHIP
    ];

    public const RELATIONSHIP_CHANGE_ACTIONS = [
        ApiActions::UPDATE_RELATIONSHIP,
        ApiActions::ADD_RELATIONSHIP,
        ApiActions::DELETE_RELATIONSHIP
    ];

    /**
     * @param ApiResource $resource
     *
     * @return bool
     */
    public static function isSubresourcesEnabled(ApiResource $resource): bool
    {
        return !\in_array(ApiActions::GET_SUBRESOURCE, $resource->getExcludedActions(), true);
    }

    /**
     * @param ApiSubresource $subresource
     * @param array          $accessibleResources
     *
     * @return bool
     */
    public static function isAccessibleSubresource(ApiSubresource $subresource, array $accessibleResources): bool
    {
        $targetClassNames = $subresource->getAcceptableTargetClassNames();
        if (empty($targetClassNames)) {
            $targetClassName = $subresource->getTargetClassName();
            if (\is_a($targetClassName, EntityIdentifier::class, true)) {
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
        if (\is_a($targetClassName, EntityIdentifier::class, true)) {
            $subresource->setAcceptableTargetClassNames($acceptableTargetClassNames);
        } elseif (!empty($acceptableTargetClassNames)
            && (
                \count($acceptableTargetClassNames) > 1
                || \reset($acceptableTargetClassNames) !== $targetClassName
            )
        ) {
            foreach ($acceptableTargetClassNames as $className) {
                if (!\is_a($className, $targetClassName, true)) {
                    throw new \RuntimeException(\sprintf(
                        'The acceptable target class "%s" should be "%s" or its subclass.',
                        $className,
                        $targetClassName
                    ));
                }
            }
            $subresource->setAcceptableTargetClassNames($acceptableTargetClassNames);
        }
    }

    /**
     * @param ApiSubresource $subresource
     * @param array          $accessibleResources
     * @param array|null     $subresourceExcludedActions
     */
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
                self::ensureActionExcluded($subresource, ApiActions::ADD_RELATIONSHIP);
                self::ensureActionExcluded($subresource, ApiActions::DELETE_RELATIONSHIP);
            }
        } else {
            $subresource->setExcludedActions(self::SUBRESOURCE_ACTIONS);
        }
    }

    /**
     * @param ApiSubresource $subresource
     * @param string         $action
     */
    public static function ensureActionExcluded(ApiSubresource $subresource, string $action): void
    {
        if (!$subresource->isExcludedAction($action)) {
            $subresource->addExcludedAction($action);
        }
    }
}
