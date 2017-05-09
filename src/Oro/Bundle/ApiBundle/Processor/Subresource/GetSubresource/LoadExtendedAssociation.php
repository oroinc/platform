<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource;

use Doctrine\DBAL\Types\Type;

use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadExtendedAssociation as BaseLoadExtendedAssociation;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

/**
 * Loads extended association data using the EntitySerializer component
 * and, if it was requested, adds "title" meta property value to each result item.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadExtendedAssociation extends BaseLoadExtendedAssociation
{
    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param EntitySerializer   $entitySerializer
     * @param DoctrineHelper     $doctrineHelper
     * @param AssociationManager $associationManager
     */
    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        AssociationManager $associationManager
    ) {
        parent::__construct($entitySerializer, $doctrineHelper);
        $this->associationManager = $associationManager;
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
                $titlePropertyPath = ConfigUtil::getPropertyPathOfMetaProperty(
                    LoadTitleMetaProperty::TITLE_META_PROPERTY_NAME,
                    $context->getConfig()
                );
                if ($titlePropertyPath) {
                    $parentEntityConfig = $context->getParentConfig();
                    list($dataType, $associationOwnerClass, $associationPath) =
                        $this->getAssociationInfo(
                            $context->getParentClassName(),
                            $parentEntityConfig,
                            $associationName
                        );
                    list($associationType, $associationKind) = DataType::parseExtendedAssociation($dataType);
                    $associationOwnerId = $this->getAssociationOwnerId(
                        $parentEntityData,
                        $parentEntityConfig,
                        $associationPath
                    );

                    if (null !== $associationOwnerId) {
                        if (!$isCollection) {
                            $data = [$data];
                        }
                        $data = $this->addTitles(
                            $data,
                            $associationOwnerClass,
                            $associationOwnerId,
                            $associationType,
                            $associationKind,
                            $titlePropertyPath
                        );
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
     * @param array       $data
     * @param string      $associationOwnerClass
     * @param mixed       $associationOwnerId
     * @param string      $associationType
     * @param string|null $associationKind
     * @param string      $titleFieldName
     *
     * @return array
     */
    protected function addTitles(
        array $data,
        $associationOwnerClass,
        $associationOwnerId,
        $associationType,
        $associationKind,
        $titleFieldName
    ) {
        $associationTargets = $this->associationManager->getAssociationTargets(
            $associationOwnerClass,
            null,
            $associationType,
            $associationKind
        );

        $targets = [];
        $dataMap = [];
        foreach ($data as $key => $item) {
            $entityClass = $item[ConfigUtil::CLASS_NAME];
            $entityId = $item['id'];
            if (!isset($targets[$entityClass])) {
                $targets[$entityClass] = [$associationTargets[$entityClass], []];
            }
            $targets[$entityClass][1][] = $entityId;
            $dataMap[$this->buildEntityKey($entityClass, $entityId)] = $key;
        }

        $titles = $this->getTitles($associationOwnerClass, $associationOwnerId, $targets);
        foreach ($titles as $item) {
            $key = $dataMap[$this->buildEntityKey($item['entity'], $item['id'])];
            $data[$key][$titleFieldName] = $item['title'];
        }

        return $data;
    }

    /**
     * @param string $associationOwnerClass
     * @param mixed  $associationOwnerId
     * @param array  $targets [target entity class => [target field name, [target id, ...]], ...]
     *
     * @return array [['entity' => entity class, 'id' => entity id, 'title' => entity title], ...]
     */
    protected function getTitles(
        $associationOwnerClass,
        $associationOwnerId,
        array $targets
    ) {
        $qb = new UnionQueryBuilder($this->doctrineHelper->getEntityManagerForClass($associationOwnerClass));
        $qb
            ->addSelect('entityId', 'id', Type::INTEGER)
            ->addSelect('entityClass', 'entity')
            ->addSelect('entityTitle', 'title');
        foreach ($targets as $targetEntityClass => $info) {
            list($targetFieldName, $targetIds) = $info;
            $subQb = $this->associationManager->getAssociationSubQueryBuilder(
                $associationOwnerClass,
                $targetEntityClass,
                $targetFieldName
            );
            $subQb
                ->andWhere($subQb->expr()->eq('e.id', $associationOwnerId))
                ->andWhere($subQb->expr()->in('target.id', $targetIds));
            $qb->addSubQuery($subQb->getQuery());
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param mixed                  $parentEntityData
     * @param EntityDefinitionConfig $parentEntityConfig
     * @param string[]               $associationPath
     *
     * @return mixed|null
     */
    protected function getAssociationOwnerId(
        $parentEntityData,
        EntityDefinitionConfig $parentEntityConfig,
        array $associationPath
    ) {
        $associationOwnerId = null;
        if (empty($associationPath)) {
            $associationOwnerId = $this->getEntityId($parentEntityData, $parentEntityConfig);
        } elseif (!empty($parentEntityData)) {
            $currentConfig = $parentEntityConfig;
            $currentData = $parentEntityData;
            foreach ($associationPath as $fieldName) {
                if (!is_array($currentData) || !array_key_exists($fieldName, $currentData)) {
                    $currentConfig = null;
                    $currentData = null;
                    break;
                }
                $fieldConfig = $currentConfig->findField($fieldName, true);
                if (null === $fieldConfig) {
                    $currentData = null;
                    $currentConfig = null;
                    break;
                }
                $currentConfig = $fieldConfig->getTargetEntity();
                if (null === $currentConfig) {
                    $currentData = null;
                    break;
                }
                $currentData = $currentData[$fieldName];
            }
            if (null !== $currentConfig) {
                $associationOwnerId = $this->getEntityId($currentData, $currentConfig);
            }
        }

        return $associationOwnerId;
    }

    /**
     * @param mixed                  $data
     * @param EntityDefinitionConfig $config
     *
     * @return mixed
     */
    protected function getEntityId($data, EntityDefinitionConfig $config)
    {
        $entityId = null;
        $idFieldNames = $config->getIdentifierFieldNames();
        if (1 === count($idFieldNames)) {
            $idFieldName = reset($idFieldNames);
            if (is_array($data) && array_key_exists($idFieldName, $data)) {
                $entityId = $data[$idFieldName];
            }
        }

        return $entityId;
    }

    /**
     * @param string                 $parentEntityClass
     * @param EntityDefinitionConfig $parentEntityConfig
     * @param string                 $associationName
     *
     * @return array [data type, association owner class, association path]
     */
    protected function getAssociationInfo(
        $parentEntityClass,
        EntityDefinitionConfig $parentEntityConfig,
        $associationName
    ) {
        $associationOwnerClass = null;
        $associationPath = [];
        $association = $parentEntityConfig->getField($associationName);
        $dataType = $association->getDataType();
        if ($dataType) {
            $associationOwnerClass = $parentEntityClass;
        } else {
            $propertyPath = $association->getPropertyPath();
            if ($propertyPath) {
                $path = ConfigUtil::explodePropertyPath($propertyPath);
                $targetFieldPath = array_slice($path, 0, -1);
                $targetField = $parentEntityConfig->findFieldByPath($targetFieldPath, true);
                if (null !== $targetField) {
                    $targetConfig = $targetField->getTargetEntity();
                    if (null !== $targetConfig) {
                        $field = $targetConfig->findField($path[count($path) - 1]);
                        if (null !== $field) {
                            $dataType = $field->getDataType();
                            $associationOwnerClass = $targetField->getTargetClass();
                            $associationPath = $targetFieldPath;
                        }
                    }
                }
            }
        }

        return [$dataType, $associationOwnerClass, $associationPath];
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
