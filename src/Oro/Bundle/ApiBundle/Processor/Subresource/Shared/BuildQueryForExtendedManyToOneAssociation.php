<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Builds ORM QueryBuilder object that will be used to get a target entity
 * of extended "many-to-one" association.
 */
class BuildQueryForExtendedManyToOneAssociation implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $associationConfig = $context->getParentConfig()->getField($context->getAssociationName());
        if (!$this->isExtendedManyToOneAssociation($associationConfig)) {
            // this processor is intended to work only with extended "many-to-one" association
            return;
        }

        $parentEntityClass = $context->getParentClassName();
        $query = $this->doctrineHelper->getEntityRepositoryForClass($parentEntityClass)->createQueryBuilder('e');
        $this->doctrineHelper->applyEntityIdentifierRestriction(
            $query,
            $parentEntityClass,
            $context->getParentId()
        );

        $context->setQuery($query);
    }

    /**
     * @param EntityDefinitionFieldConfig $associationConfig
     *
     * @return bool
     */
    protected function isExtendedManyToOneAssociation(EntityDefinitionFieldConfig $associationConfig)
    {
        $associationDataType = $associationConfig->getDataType();
        if (!DataType::isExtendedAssociation($associationDataType)) {
            return false;
        }

        list($type,) = DataType::parseExtendedAssociation($associationDataType);

        return RelationType::MANY_TO_ONE === $type;
    }
}
