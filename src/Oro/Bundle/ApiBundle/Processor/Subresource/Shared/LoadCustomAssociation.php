<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

abstract class LoadCustomAssociation implements ProcessorInterface
{
    /** @var EntitySerializer */
    protected $entitySerializer;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityIdHelper */
    protected $entityIdHelper;

    /**
     * @param EntitySerializer $entitySerializer
     * @param DoctrineHelper   $doctrineHelper
     * @param EntityIdHelper   $entityIdHelper
     */
    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        EntityIdHelper $entityIdHelper
    ) {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $associationName = $context->getAssociationName();
        $dataType = $this->findFieldDataType($context->getParentConfig(), $associationName);
        if ($dataType && $this->isSupportedAssociation($dataType)) {
            $this->loadAssociationData($context, $associationName, $dataType);
        }
    }

    /**
     * @param string $dataType
     *
     * @return bool
     */
    abstract protected function isSupportedAssociation($dataType);

    /**
     * @param SubresourceContext $context
     * @param string             $associationName
     * @param string             $dataType
     */
    abstract protected function loadAssociationData(SubresourceContext $context, $associationName, $dataType);

    /**
     * @param SubresourceContext $context
     * @param mixed              $data
     */
    protected function saveAssociationDataToContext(SubresourceContext $context, $data)
    {
        $context->setResult($data);

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }

    /**
     * @param SubresourceContext $context
     * @param string             $associationName
     * @param bool               $isCollection
     *
     * @return array|null
     */
    protected function loadData(SubresourceContext $context, $associationName, $isCollection)
    {
        return $this->getAssociationData(
            $this->loadParentEntityData($context),
            $associationName,
            $isCollection
        );
    }

    /**
     * @param SubresourceContext $context
     *
     * @return array|null
     */
    protected function loadParentEntityData(SubresourceContext $context)
    {
        $data = $this->entitySerializer->serialize(
            $this->getQueryBuilder(
                $context->getParentClassName(),
                $context->getParentId(),
                $context->getParentMetadata()
            ),
            $context->getParentConfig()
        );

        return reset($data);
    }

    /**
     * @param mixed  $parentEntityData
     * @param string $associationName
     * @param bool   $isCollection
     *
     * @return array|null
     */
    protected function getAssociationData($parentEntityData, $associationName, $isCollection)
    {
        if (empty($parentEntityData) || !array_key_exists($associationName, $parentEntityData)) {
            return $isCollection ? [] : null;
        }

        $result = $parentEntityData[$associationName];
        if (!$isCollection && null !== $result && empty($result)) {
            $result = null;
        }

        return $result;
    }

    /**
     * @param string         $parentEntityClass
     * @param mixed          $parentEntityId
     * @param EntityMetadata $parentEntityMetadata
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder($parentEntityClass, $parentEntityId, EntityMetadata $parentEntityMetadata)
    {
        $query = $this->doctrineHelper->getEntityRepositoryForClass($parentEntityClass)->createQueryBuilder('e');
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $query,
            $parentEntityId,
            $parentEntityMetadata
        );

        return $query;
    }

    /**
     * @param string $associationType
     *
     * @return bool
     */
    protected function isCollection($associationType)
    {
        switch ($associationType) {
            case RelationType::MANY_TO_ONE:
                return false;
            case RelationType::MANY_TO_MANY:
            case RelationType::MULTIPLE_MANY_TO_ONE:
                return true;
            default:
                throw new \InvalidArgumentException(
                    sprintf('Unsupported type of extended association: %s.', $associationType)
                );
        }
    }

    /**
     * Finds the data-type of the given field.
     * If the "data_type" attribute is not defined for the field,
     * but the field has the "property_path" attribute the data-type of the target field is returned.
     *
     * @param EntityDefinitionConfig $config
     * @param string                 $fieldName
     *
     * @return string|null
     */
    protected function findFieldDataType(EntityDefinitionConfig $config, $fieldName)
    {
        $field = $config->findField($fieldName);
        if (null === $field) {
            return null;
        }

        $dataType = $field->getDataType();
        if (!$dataType) {
            $propertyPath = $field->getPropertyPath();
            if ($propertyPath) {
                $targetField = $config->findFieldByPath($propertyPath, true);
                if (null !== $targetField) {
                    $dataType = $targetField->getDataType();
                }
            }
        }

        return $dataType;
    }
}
