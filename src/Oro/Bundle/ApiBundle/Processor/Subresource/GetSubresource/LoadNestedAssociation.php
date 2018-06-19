<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadNestedAssociation as BaseLoadNestedAssociation;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Loads nested association data using the EntitySerializer component
 * and, if it was requested, adds "title" meta property value to each result item.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadNestedAssociation extends BaseLoadNestedAssociation
{
    /** @var EntityTitleProvider */
    protected $entityTitleProvider;

    /**
     * @param EntitySerializer    $entitySerializer
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityIdHelper      $entityIdHelper
     * @param EntityTitleProvider $entityTitleProvider
     */
    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        EntityIdHelper $entityIdHelper,
        EntityTitleProvider $entityTitleProvider
    ) {
        parent::__construct($entitySerializer, $doctrineHelper, $entityIdHelper);
        $this->entityTitleProvider = $entityTitleProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData(SubresourceContext $context, $associationName, $isCollection)
    {
        $data = parent::loadData($context, $associationName, $isCollection);

        if (!$context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME)) {
            if (!empty($data)) {
                $config = $context->getConfig();
                if (null !== $config) {
                    $titlePropertyPath = ConfigUtil::getPropertyPathOfMetaProperty(
                        LoadTitleMetaProperty::TITLE_META_PROPERTY_NAME,
                        $config
                    );
                    if ($titlePropertyPath) {
                        $data = $this->addTitle($data, $titlePropertyPath, $config);
                    }
                }
            }
            $context->setProcessed(LoadTitleMetaProperty::OPERATION_NAME);
        }

        return $data;
    }

    /**
     * @param array                  $item
     * @param string                 $titleFieldName
     * @param EntityDefinitionConfig $config
     *
     * @return array
     */
    protected function addTitle(array $item, $titleFieldName, EntityDefinitionConfig $config)
    {
        $entityClass = $item[ConfigUtil::CLASS_NAME];
        $entityId = $item['id'];

        $title = $this->getTitle($entityClass, $entityId, $config);
        if (null !== $title) {
            $item[$titleFieldName] = $title;
        }

        return $item;
    }

    /**
     * @param string                 $entityClass
     * @param string                 $entityId
     * @param EntityDefinitionConfig $config
     *
     * @return string|null
     */
    protected function getTitle($entityClass, $entityId, EntityDefinitionConfig $config)
    {
        $titles = $this->entityTitleProvider->getTitles([
            $entityClass => [$this->getEntityIdentifierFieldName($config), [$entityId]]
        ]);
        if (empty($titles)) {
            return null;
        }

        $row = reset($titles);
        if ($entityClass !== $row['entity'] || $entityId !== $row['id']) {
            return null;
        }

        return $row['title'];
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return string|string[]|null
     */
    protected function getEntityIdentifierFieldName(EntityDefinitionConfig $config)
    {
        $fieldNames = $config->getIdentifierFieldNames();
        $numberOfFields = count($fieldNames);
        if (0 === $numberOfFields) {
            return null;
        }
        if (1 === $numberOfFields) {
            return reset($fieldNames);
        }

        return $fieldNames;
    }
}
