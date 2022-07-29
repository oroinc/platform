<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Component\EntitySerializer\SerializationHelper;

/**
 * Provides functionality to load data for an association that can contain different entity types.
 */
class MultiTargetAssociationDataLoader
{
    private ConfigProvider $configProvider;
    private EntitySerializer $entitySerializer;
    private SerializationHelper $serializationHelper;
    private DoctrineHelper $doctrineHelper;
    private ResourcesProvider $resourcesProvider;

    public function __construct(
        ConfigProvider $configProvider,
        EntitySerializer $entitySerializer,
        SerializationHelper $serializationHelper,
        DoctrineHelper $doctrineHelper,
        ResourcesProvider $resourcesProvider
    ) {
        $this->configProvider = $configProvider;
        $this->entitySerializer = $entitySerializer;
        $this->serializationHelper = $serializationHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * @return array|null [entity id => entity data, ...]
     */
    public function loadExpandedEntityData(
        string $entityClass,
        array $entityIds,
        string $entityIdFieldName,
        CustomizeLoadedDataContext $context,
        ?string $associationPath
    ): ?array {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        if (!$this->resourcesProvider->isResourceAccessibleAsAssociation($entityClass, $version, $requestType)) {
            return null;
        }

        $configExtras = TargetConfigExtraBuilder::buildConfigExtras(
            $context->getConfigExtras(),
            $associationPath
        );
        $entityConfig = $this->configProvider
            ->getConfig($entityClass, $version, $requestType, $configExtras)
            ->getDefinition();
        if (null === $entityConfig) {
            return null;
        }

        $normalizationContext = $context->getNormalizationContext();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $items = $this->loadExpandedDataForManageableEntities(
                $entityClass,
                $entityIds,
                $entityConfig,
                $normalizationContext
            );
        } else {
            $items = $this->loadExpandedDataForNotManageableEntities(
                $entityClass,
                $entityIds,
                $entityConfig,
                $normalizationContext
            );
        }

        $result = [];
        foreach ($items as $item) {
            if (!isset($item[ConfigUtil::CLASS_NAME])) {
                $item[ConfigUtil::CLASS_NAME] = $entityClass;
            }
            $result[$item[$entityIdFieldName]] = $item;
        }

        return $result;
    }

    private function loadExpandedDataForManageableEntities(
        string $entityClass,
        array $entityIds,
        EntityDefinitionConfig $entityConfig,
        array $normalizationContext
    ): array {
        $qb = $this->doctrineHelper->createQueryBuilder($entityClass, 'e')
            ->where('e IN (:ids)')
            ->setParameter('ids', $entityIds);

        return $this->entitySerializer->serialize($qb, $entityConfig, $normalizationContext);
    }

    private function loadExpandedDataForNotManageableEntities(
        string $entityClass,
        array $entityIds,
        EntityDefinitionConfig $entityConfig,
        array $normalizationContext
    ): array {
        $idFieldNames = $entityConfig->getIdentifierFieldNames();
        if (!$idFieldNames) {
            return [];
        }

        $items = [];
        $idFieldName = reset($idFieldNames);
        foreach ($entityIds as $entityId) {
            $items[] = $this->serializationHelper->postSerializeItem(
                [ConfigUtil::CLASS_NAME => $entityClass, $idFieldName => $entityId],
                $entityConfig,
                $normalizationContext
            );
        }

        return $this->serializationHelper->postSerializeCollection(
            $items,
            $entityConfig,
            $normalizationContext
        );
    }
}
