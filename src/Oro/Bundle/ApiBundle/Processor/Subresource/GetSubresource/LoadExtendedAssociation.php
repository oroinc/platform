<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource;

use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadExtendedAssociation as BaseLoadExtendedAssociation;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Loads extended association data using the EntitySerializer component
 * and, if it was requested, adds "title" meta property value to each result item.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadExtendedAssociation extends BaseLoadExtendedAssociation
{
    /** @var AssociationManager */
    protected $associationManager;

    /** @var EntityTitleProvider */
    protected $entityTitleProvider;

    /**
     * @param EntitySerializer    $entitySerializer
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityIdHelper      $entityIdHelper
     * @param AssociationManager  $associationManager
     * @param EntityTitleProvider $entityTitleProvider
     */
    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        EntityIdHelper $entityIdHelper,
        AssociationManager $associationManager,
        EntityTitleProvider $entityTitleProvider
    ) {
        parent::__construct($entitySerializer, $doctrineHelper, $entityIdHelper);
        $this->associationManager = $associationManager;
        $this->entityTitleProvider = $entityTitleProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData(SubresourceContext $context, $associationName, $isCollection)
    {
        $parentEntityData = $this->loadParentEntityData($context);
        $data = $this->getAssociationData($parentEntityData, $associationName, $isCollection);

        if (!$context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME)) {
            if (!empty($data)) {
                $config = $context->getConfig();
                if (null !== $config) {
                    $titlePropertyPath = ConfigUtil::getPropertyPathOfMetaProperty(
                        LoadTitleMetaProperty::TITLE_META_PROPERTY_NAME,
                        $config
                    );
                    if ($titlePropertyPath) {
                        if (!$isCollection) {
                            $data = [$data];
                        }
                        $data = $this->addTitles($data, $titlePropertyPath);
                        if (!$isCollection) {
                            $data = reset($data);
                        }
                    }
                }
            }
            $context->setProcessed(LoadTitleMetaProperty::OPERATION_NAME);
        }

        return $data;
    }

    /**
     * @param array  $data
     * @param string $titleFieldName
     *
     * @return array
     */
    protected function addTitles(array $data, $titleFieldName)
    {
        $targets = [];
        $dataMap = [];
        foreach ($data as $key => $item) {
            $entityClass = $item[ConfigUtil::CLASS_NAME];
            $entityId = $item['id'];
            if (!isset($targets[$entityClass])) {
                $targets[$entityClass] = [
                    $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass),
                    []
                ];
            }
            $targets[$entityClass][1][] = $entityId;
            $dataMap[$this->buildEntityKey($entityClass, $entityId)] = $key;
        }

        $titles = $this->entityTitleProvider->getTitles($targets);
        foreach ($titles as $item) {
            $key = $dataMap[$this->buildEntityKey($item['entity'], $item['id'])];
            $data[$key][$titleFieldName] = $item['title'];
        }

        return $data;
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return string
     */
    protected function buildEntityKey($entityClass, $entityId)
    {
        return $entityClass . '::' . $entityId;
    }
}
