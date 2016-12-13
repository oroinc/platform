<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Loads extended association data using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadExtendedAssociation implements ProcessorInterface
{
    /** @var EntitySerializer */
    protected $entitySerializer;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param EntitySerializer $entitySerializer
     * @param DoctrineHelper   $doctrineHelper
     */
    public function __construct(EntitySerializer $entitySerializer, DoctrineHelper $doctrineHelper)
    {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
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
        $parentConfig = $context->getParentConfig();
        $associationType = $this->getExtendedAssociationType($associationName, $parentConfig);
        if (!$associationType) {
            // only extended association is supported
            return;
        }

        $context->setResult(
            $this->loadData($context, $associationName, $this->isCollection($associationType))
        );

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }

    /**
     * @param string                 $associationName
     * @param EntityDefinitionConfig $parentConfig
     *
     * @return string|null
     */
    protected function getExtendedAssociationType($associationName, EntityDefinitionConfig $parentConfig)
    {
        $associationConfig = $parentConfig->getField($associationName);
        if (null === $associationConfig) {
            return null;
        }

        $dataType = $associationConfig->getDataType();
        if (!DataType::isExtendedAssociation($dataType)) {
            return null;
        }

        list($associationType,) = DataType::parseExtendedAssociation($dataType);

        return $associationType;
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
     * @param SubresourceContext $context
     * @param string             $associationName
     * @param bool               $isCollection
     *
     * @return array|null
     */
    protected function loadData(SubresourceContext $context, $associationName, $isCollection)
    {
        $data = $this->entitySerializer->serialize(
            $this->getQueryBuilder($context->getParentClassName(), $context->getParentId()),
            $context->getParentConfig()
        );
        $data = reset($data);
        if (empty($data) || !array_key_exists($associationName, $data)) {
            return $isCollection ? [] : null;
        }

        $result = $data[$associationName];
        if (!$isCollection && null !== $result && empty($result)) {
            $result = null;
        }

        return $result;
    }

    /**
     * @param string $parentEntityClass
     * @param mixed  $parentEntityId
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder($parentEntityClass, $parentEntityId)
    {
        $query = $this->doctrineHelper->getEntityRepositoryForClass($parentEntityClass)->createQueryBuilder('e');
        $this->doctrineHelper->applyEntityIdentifierRestriction(
            $query,
            $parentEntityClass,
            $parentEntityId
        );

        return $query;
    }
}
