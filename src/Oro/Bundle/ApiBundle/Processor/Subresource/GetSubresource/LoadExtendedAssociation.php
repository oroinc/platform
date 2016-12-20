<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource;

use Doctrine\DBAL\Types\Type;

use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
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
        $data = parent::loadData($context, $associationName, $isCollection);

        if (!empty($data) && !$context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME)) {
            $titlePropertyPath = ConfigUtil::getPropertyPathOfMetaProperty(
                LoadTitleMetaProperty::TITLE_META_PROPERTY_NAME,
                $context->getConfig()
            );
            if ($titlePropertyPath) {
                if (!$isCollection) {
                    $data = [$data];
                }
                $data = $this->addTitles(
                    $data,
                    $context->getParentClassName(),
                    $context->getParentId(),
                    $context->getParentConfig()->getField($associationName),
                    $titlePropertyPath
                );
                if (!$isCollection) {
                    $data = reset($data);
                }
                $context->setProcessed(LoadTitleMetaProperty::OPERATION_NAME);
            }
        }

        return $data;
    }

    /**
     * @param array                       $data
     * @param string                      $parentEntityClass
     * @param mixed                       $parentEntityId
     * @param EntityDefinitionFieldConfig $association
     * @param string                      $titleFieldName
     *
     * @return array
     */
    protected function addTitles(
        array $data,
        $parentEntityClass,
        $parentEntityId,
        EntityDefinitionFieldConfig $association,
        $titleFieldName
    ) {
        $associationTargets = $this->getAssociationTargets($parentEntityClass, $association);

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

        $titles = $this->getTitles($parentEntityClass, $parentEntityId, $targets);
        foreach ($titles as $item) {
            $key = $dataMap[$this->buildEntityKey($item['entity'], $item['id'])];
            $data[$key][$titleFieldName] = $item['title'];
        }

        return $data;
    }

    /**
     * @param string $associationOwnerClass
     * @param string $ownerEntityId
     * @param array  $targets
     *
     * @return array [['entity' => entity class, 'id' => entity id, 'title' => entity title], ...]
     */
    protected function getTitles($associationOwnerClass, $ownerEntityId, array $targets)
    {
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
                ->andWhere($subQb->expr()->eq('e.id', $ownerEntityId))
                ->andWhere($subQb->expr()->in('target.id', $targetIds));
            $qb->addSubQuery($subQb->getQuery());
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param string                      $parentEntityClass
     * @param EntityDefinitionFieldConfig $association
     *
     * @return array
     */
    protected function getAssociationTargets($parentEntityClass, EntityDefinitionFieldConfig $association)
    {
        list($associationType, $associationKind) = DataType::parseExtendedAssociation(
            $association->getDataType()
        );

        return $this->associationManager->getAssociationTargets(
            $parentEntityClass,
            null,
            $associationType,
            $associationKind
        );
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
