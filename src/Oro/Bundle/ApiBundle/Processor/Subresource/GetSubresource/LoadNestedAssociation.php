<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource;

use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadNestedAssociation as BaseLoadNestedAssociation;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

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
     * @param EntityTitleProvider $entityTitleProvider
     */
    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        EntityTitleProvider $entityTitleProvider
    ) {
        parent::__construct($entitySerializer, $doctrineHelper);
        $this->entityTitleProvider = $entityTitleProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData(SubresourceContext $context, $associationName, $isCollection)
    {
        $data = parent::loadData($context, $associationName, $isCollection);

        if (!empty($data) && !$context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME)) {
            $titlePropertyPath = ConfigUtil::getPropertyPathOfMetaProperty(
                LoadTitleMetaProperty::TITLE_META_PROPERTY_NAME,
                $context->getConfig()
            );
            if ($titlePropertyPath) {
                $data = $this->addTitle($data, $titlePropertyPath);
                $context->setProcessed(LoadTitleMetaProperty::OPERATION_NAME);
            }
        }

        return $data;
    }

    /**
     * @param array  $item
     * @param string $titleFieldName
     *
     * @return array
     */
    protected function addTitle(array $item, $titleFieldName)
    {
        $entityClass = $item[ConfigUtil::CLASS_NAME];
        $entityId = $item['id'];

        $title = $this->getTitle($entityClass, $entityId);
        if (null !== $title) {
            $item[$titleFieldName] = $title;
        }

        return $item;
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     *
     * @return string|null
     */
    protected function getTitle($entityClass, $entityId)
    {
        $titles = $this->entityTitleProvider->getTitles([$entityClass => [$entityId]]);
        if (empty($titles)) {
            return null;
        }

        $row = reset($titles);
        if ($entityClass !== $row['entity'] || $entityId !== $row['id']) {
            return null;
        }

        return $row['title'];
    }
}
