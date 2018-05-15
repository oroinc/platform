<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base class for different kind of processors that load sub-resources.
 */
abstract class LoadSubresources implements ProcessorInterface
{
    protected const SUBRESOURCE_DEFAULT_EXCLUDED_ACTIONS = [
        ApiActions::UPDATE_SUBRESOURCE,
        ApiActions::ADD_SUBRESOURCE,
        ApiActions::DELETE_SUBRESOURCE
    ];

    protected const SUBRESOURCE_ACTIONS = [
        ApiActions::GET_SUBRESOURCE,
        ApiActions::UPDATE_SUBRESOURCE,
        ApiActions::ADD_SUBRESOURCE,
        ApiActions::DELETE_SUBRESOURCE,
        ApiActions::GET_RELATIONSHIP,
        ApiActions::UPDATE_RELATIONSHIP,
        ApiActions::ADD_RELATIONSHIP,
        ApiActions::DELETE_RELATIONSHIP
    ];

    protected const RELATIONSHIP_CHANGE_ACTIONS = [
        ApiActions::UPDATE_RELATIONSHIP,
        ApiActions::ADD_RELATIONSHIP,
        ApiActions::DELETE_RELATIONSHIP
    ];

    /** @var ConfigProvider */
    private $configProvider;

    /** @var MetadataProvider */
    private $metadataProvider;

    /**
     * @param ConfigProvider   $configProvider
     * @param MetadataProvider $metadataProvider
     */
    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * @param AssociationMetadata $association
     * @param array               $accessibleResources
     * @param string[]            $subresourceExcludedActions
     *
     * @return ApiSubresource
     */
    protected function createSubresource(
        AssociationMetadata $association,
        array $accessibleResources,
        array $subresourceExcludedActions
    ): ApiSubresource {
        $subresource = new ApiSubresource();
        $subresource->setTargetClassName($association->getTargetClassName());
        $subresource->setAcceptableTargetClassNames($association->getAcceptableTargetClassNames());
        $subresource->setIsCollection($association->isCollection());
        if ($this->isAccessibleSubresource($subresource, $accessibleResources)) {
            if (!empty($subresourceExcludedActions)) {
                $subresource->setExcludedActions($subresourceExcludedActions);
            }
            if (!$association->isCollection()) {
                $this->ensureActionExcluded($subresource, ApiActions::ADD_RELATIONSHIP);
                $this->ensureActionExcluded($subresource, ApiActions::DELETE_RELATIONSHIP);
            }
        } else {
            $subresource->setExcludedActions(self::SUBRESOURCE_ACTIONS);
        }

        return $subresource;
    }

    /**
     * @param ApiSubresource $subresource
     * @param string         $action
     */
    protected function ensureActionExcluded(ApiSubresource $subresource, string $action): void
    {
        if (!$subresource->isExcludedAction($action)) {
            $subresource->addExcludedAction($action);
        }
    }

    /**
     * @param ApiResource $resource
     *
     * @return string[]
     */
    protected function getSubresourceExcludedActions(ApiResource $resource): array
    {
        $resourceExcludedActions = $resource->getExcludedActions();
        if (empty($resourceExcludedActions)) {
            return self::SUBRESOURCE_DEFAULT_EXCLUDED_ACTIONS;
        }

        // keep only sub-resource related actions
        $result = \array_intersect($resourceExcludedActions, self::SUBRESOURCE_ACTIONS);
        // make sure that default excluded actions for sub-resource exist
        $result = \array_merge($result, self::SUBRESOURCE_DEFAULT_EXCLUDED_ACTIONS);
        // disable changes of relationships if the parent entity modification is disabled
        if (\in_array(ApiActions::UPDATE, $resourceExcludedActions, true)) {
            $result = \array_merge($result, self::RELATIONSHIP_CHANGE_ACTIONS);
        }

        return \array_values(\array_unique($result));
    }

    /**
     * @param ApiResource $resource
     *
     * @return bool
     */
    protected function isSubresourcesEnabled(ApiResource $resource): bool
    {
        return !\in_array(ApiActions::GET_SUBRESOURCE, $resource->getExcludedActions(), true);
    }

    /**
     * @param string                 $fieldName
     * @param EntityDefinitionConfig $config
     *
     * @return bool
     */
    protected function isExcludedAssociation(string $fieldName, EntityDefinitionConfig $config): bool
    {
        $field = $config->getField($fieldName);
        if (null === $field) {
            return false;
        }

        return
            $field->isExcluded()
            || DataType::isAssociationAsField($field->getDataType());
    }

    /**
     * @param ApiSubresource $subresource
     * @param array          $accessibleResources
     *
     * @return bool
     */
    protected function isAccessibleSubresource(ApiSubresource $subresource, array $accessibleResources): bool
    {
        $targetClassNames = $subresource->getAcceptableTargetClassNames();
        if (empty($targetClassNames)) {
            return true;
        }

        foreach ($targetClassNames as $className) {
            if (isset($accessibleResources[$className])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return EntityDefinitionConfig|null
     */
    protected function getConfig(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): ?EntityDefinitionConfig {
        return $this->configProvider
            ->getConfig($entityClass, $version, $requestType, [new EntityDefinitionConfigExtra()])
            ->getDefinition();
    }

    /**
     * @param string                 $entityClass
     * @param string                 $version
     * @param RequestType            $requestType
     * @param EntityDefinitionConfig $config
     *
     * @return EntityMetadata|null
     */
    protected function getMetadata(
        string $entityClass,
        string $version,
        RequestType $requestType,
        EntityDefinitionConfig $config
    ): ?EntityMetadata {
        return $this->metadataProvider->getMetadata($entityClass, $version, $requestType, $config, [], true);
    }
}
