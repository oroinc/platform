<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Initializes sub-resources for all API resources based on API configuration and metadata.
 */
class InitializeSubresources implements ProcessorInterface
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectSubresourcesContext $context */

        $subresources = $context->getResult();
        if (!$subresources->isEmpty()) {
            // already initialized
            return;
        }

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $configExtras = $this->getConfigExtras();
        $metadataExtras = $this->getMetadataExtras();

        $accessibleResources = array_fill_keys($context->getAccessibleResources(), true);
        $resources = $context->getResources();
        foreach ($resources as $resource) {
            $subresources->add(
                $this->createEntitySubresources(
                    $resource,
                    $version,
                    $requestType,
                    $configExtras,
                    $metadataExtras,
                    $accessibleResources
                )
            );
        }
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

    /**
     * @param ApiResource              $resource
     * @param string                   $version
     * @param RequestType              $requestType
     * @param ConfigExtraInterface[]   $configExtras
     * @param MetadataExtraInterface[] $metadataExtras
     * @param array                    $accessibleResources
     *
     * @return ApiResourceSubresources
     */
    protected function createEntitySubresources(
        ApiResource $resource,
        $version,
        RequestType $requestType,
        array $configExtras,
        array $metadataExtras,
        array $accessibleResources
    ) {
        $entityClass = $resource->getEntityClass();
        $config = $this->configProvider->getConfig(
            $entityClass,
            $version,
            $requestType,
            $configExtras
        );
        $metadata = $this->metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            $config->getDefinition(),
            $metadataExtras
        );
        if (null === $metadata) {
            throw new RuntimeException(sprintf('A metadata for "%s" entity does not exist.', $entityClass));
        }

        $resourceExcludedActions = $resource->getExcludedActions();
        $subresourceExcludedActions = !empty($resourceExcludedActions)
            ? $this->getSubresourceExcludedActions($resourceExcludedActions)
            : [];

        $entitySubresources = new ApiResourceSubresources($entityClass);
        $associations = $metadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            $subresource = $entitySubresources->addSubresource($associationName);
            $subresource->setTargetClassName($association->getTargetClassName());
            $subresource->setAcceptableTargetClassNames($association->getAcceptableTargetClassNames());
            $subresource->setIsCollection($association->isCollection());
            if ($association->isCollection()) {
                if (!$this->isAccessibleAssociation($association, $accessibleResources)) {
                    $subresource->setExcludedActions($this->getToManyRelationshipsActions());
                } elseif (!empty($subresourceExcludedActions)) {
                    $subresource->setExcludedActions($subresourceExcludedActions);
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
        }

        return $entitySubresources;
    }

    /**
     * @param string[] $resourceExcludedActions
     *
     * @return string[]
     */
    protected function getSubresourceExcludedActions(array $resourceExcludedActions)
    {
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
}
