<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\GetRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Builds ORM QueryBuilder object that will be used to get an entity by its parent class and parent identifier.
 * Used for extended Many-To-One associations in scope of 'get_relationship' and 'get_subresource' requests.
 */
class BuildQueryByExtendedAssociation implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CriteriaConnector */
    protected $criteriaConnector;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityClassResolver $entityClassResolver
     * @param CriteriaConnector   $criteriaConnector
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        CriteriaConnector $criteriaConnector
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->criteriaConnector = $criteriaConnector;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetRelationshipContext|GetSubresourceContext $context */

        if (null !== $context->getCriteria()) {
            // the criteria object is already initialized
            return;
        }

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // an entity configuration does not exist
            return;
        }

        $parentConfig = $context->getParentConfig();
        if (null === $parentConfig) {
            // a parent entity configuration does not exist
            return;
        }

        $associationConfig = $parentConfig->getField($context->getAssociationName());
        $associationDataType = $associationConfig->getDataType();
        if (!DataType::isExtendedAssociation($associationDataType)) {
            // an association is not extended
            return;
        }

        list($type, ) = DataType::parseExtendedAssociation($associationDataType);
        if ($type !== RelationType::MANY_TO_ONE) {
            // only many-to-one association is supported
            return;
        }

        $entityClass = $context->getParentClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $criteria = new Criteria($this->entityClassResolver);
        $context->setCriteria($criteria);

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->doctrineHelper->applyEntityIdentifierRestriction($query, $entityClass, $context->getParentId());
        $this->criteriaConnector->applyCriteria($query, $criteria);

        $context->setQuery($query);
    }
}
