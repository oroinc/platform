<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;

abstract class LoadSubresources implements ProcessorInterface
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

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
     * @param array               $subresourceExcludedActions
     *
     * @return ApiSubresource
     */
    protected function createSubresource(
        AssociationMetadata $association,
        array $accessibleResources,
        array $subresourceExcludedActions
    ) {
        $subresource = new ApiSubresource();
        $subresource->setTargetClassName($association->getTargetClassName());
        $subresource->setAcceptableTargetClassNames($association->getAcceptableTargetClassNames());
        $subresource->setIsCollection($association->isCollection());
        if ($association->isCollection()) {
            if (!$this->isAccessibleAssociation($association, $accessibleResources)) {
                $subresource->setExcludedActions($this->getToManyRelationshipsActions());
            } elseif (!empty($subresourceExcludedActions)) {
                $subresource->setExcludedActions($subresourceExcludedActions);
            }
            if ($association->has(DataType::INVERSE_ASSOCIATION_FIELD)) {
                $subresource->setExcludedActions(['update_relationship', 'delete_relationship']);
            }
        } else {
            if (!$this->isAccessibleAssociation($association, $accessibleResources)) {
                $subresource->setExcludedActions($this->getToOneRelationshipsActions());
            } else {
                $excludedActions = $subresourceExcludedActions;
                if (!in_array(ApiActions::ADD_RELATIONSHIP, $excludedActions, true)) {
                    $excludedActions[] = ApiActions::ADD_RELATIONSHIP;
                }
                if (!in_array(ApiActions::DELETE_RELATIONSHIP, $excludedActions, true)) {
                    $excludedActions[] = ApiActions::DELETE_RELATIONSHIP;
                }
                $subresource->setExcludedActions($excludedActions);
            }
        }

        return $subresource;
    }

    /**
     * @param ApiResource $resource
     *
     * @return string[]
     */
    protected function getSubresourceExcludedActions(ApiResource $resource)
    {
        $resourceExcludedActions = $resource->getExcludedActions();
        if (empty($resourceExcludedActions)) {
            return [];
        }

        $result = array_intersect(
            $resourceExcludedActions,
            [
                ApiActions::GET_SUBRESOURCE,
                ApiActions::GET_RELATIONSHIP,
                ApiActions::UPDATE_RELATIONSHIP,
                ApiActions::ADD_RELATIONSHIP,
                ApiActions::DELETE_RELATIONSHIP
            ]
        );

        if (in_array(ApiActions::UPDATE, $resourceExcludedActions, true)) {
            $result = array_unique(
                array_merge(
                    $result,
                    [
                        ApiActions::UPDATE_RELATIONSHIP,
                        ApiActions::ADD_RELATIONSHIP,
                        ApiActions::DELETE_RELATIONSHIP
                    ]
                )
            );
        }

        return array_values($result);
    }

    /**
     * @return string[]
     */
    protected function getToOneRelationshipsActions()
    {
        return [
            ApiActions::GET_SUBRESOURCE,
            ApiActions::GET_RELATIONSHIP,
            ApiActions::UPDATE_RELATIONSHIP
        ];
    }

    /**
     * @return string[]
     */
    protected function getToManyRelationshipsActions()
    {
        return [
            ApiActions::GET_SUBRESOURCE,
            ApiActions::GET_RELATIONSHIP,
            ApiActions::UPDATE_RELATIONSHIP,
            ApiActions::ADD_RELATIONSHIP,
            ApiActions::DELETE_RELATIONSHIP
        ];
    }

    /**
     * @param string                      $fieldName
     * @param EntityDefinitionConfig|null $config
     *
     * @return bool
     */
    protected function isExcludedAssociation($fieldName, EntityDefinitionConfig $config = null)
    {
        if (null === $config) {
            return false;
        }
        $field = $config->getField($fieldName);
        if (null === $field) {
            return false;
        }

        // we should not skip inverse extend associations if such fields exists in config
        if ($field->has(DataType::INVERSE_ASSOCIATION_FIELD)) {
            return false;
        }

        return
            $field->isExcluded()
            || DataType::isAssociationAsField($field->getDataType());
    }

    /**
     * @param AssociationMetadata $association
     * @param array               $accessibleResources
     *
     * @return bool
     */
    protected function isAccessibleAssociation(AssociationMetadata $association, array $accessibleResources)
    {
        $targetClassNames = $association->getAcceptableTargetClassNames();
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
    protected function getConfig($entityClass, $version, RequestType $requestType)
    {
        return $this->configProvider
            ->getConfig($entityClass, $version, $requestType, $this->getConfigExtras())
            ->getDefinition();
    }

    /**
     * @param string                      $entityClass
     * @param string                      $version
     * @param RequestType                 $requestType
     * @param EntityDefinitionConfig|null $config
     *
     * @return EntityMetadata|null
     */
    protected function getMetadata(
        $entityClass,
        $version,
        RequestType $requestType,
        EntityDefinitionConfig $config = null
    ) {
        return $this->metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            $config,
            $this->getMetadataExtras(),
            true
        );
    }

    /**
     * @return ConfigExtraInterface[]
     */
    protected function getConfigExtras()
    {
        return [new EntityDefinitionConfigExtra()];
    }

    /**
     * @return MetadataExtraInterface[]
     */
    protected function getMetadataExtras()
    {
        return [];
    }
}
