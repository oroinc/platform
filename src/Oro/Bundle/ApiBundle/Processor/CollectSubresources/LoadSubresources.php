<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
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
    private ConfigProvider $configProvider;
    private MetadataProvider $metadataProvider;

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
        $targetClassName = $association->getTargetClassName();

        $subresource = new ApiSubresource();
        $subresource->setTargetClassName($targetClassName);
        SubresourceUtil::setAcceptableTargetClasses(
            $subresource,
            $targetClassName,
            $association->getAcceptableTargetClassNames()
        );
        $subresource->setIsCollection($association->isCollection());
        SubresourceUtil::setSubresourceExcludedActions(
            $subresource,
            $accessibleResources,
            $subresourceExcludedActions
        );

        return $subresource;
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
            return SubresourceUtil::SUBRESOURCE_DEFAULT_EXCLUDED_ACTIONS;
        }

        // keep only sub-resource related actions
        $result = array_intersect($resourceExcludedActions, SubresourceUtil::SUBRESOURCE_ACTIONS);
        // make sure that default excluded actions for sub-resource exist
        $result = array_merge($result, SubresourceUtil::SUBRESOURCE_DEFAULT_EXCLUDED_ACTIONS);
        // disable changes of relationships if the parent entity modification is disabled
        if (\in_array(ApiAction::UPDATE, $resourceExcludedActions, true)) {
            $result = array_merge($result, SubresourceUtil::RELATIONSHIP_CHANGE_ACTIONS);
        }

        return array_values(array_unique($result));
    }

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

    protected function getConfig(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): ?EntityDefinitionConfig {
        return $this->configProvider
            ->getConfig($entityClass, $version, $requestType, [new EntityDefinitionConfigExtra()])
            ->getDefinition();
    }

    protected function getMetadata(
        string $entityClass,
        string $version,
        RequestType $requestType,
        EntityDefinitionConfig $config
    ): ?EntityMetadata {
        return $this->metadataProvider->getMetadata($entityClass, $version, $requestType, $config, [], true);
    }
}
