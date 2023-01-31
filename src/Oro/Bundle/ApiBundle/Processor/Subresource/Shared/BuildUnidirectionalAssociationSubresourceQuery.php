<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\UnidirectionalAssociationCompleter;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get a list of entities
 * for an unidirectional association for "get_relationship" and "get_subresource" actions.
 */
class BuildUnidirectionalAssociationSubresourceQuery implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $parentConfig = $context->getParentConfig();
        if (null === $parentConfig) {
            // not supported API resource
            return;
        }

        $associationName = $context->getAssociationName();
        $unidirectionalAssociations = $parentConfig->get(
            UnidirectionalAssociationCompleter::UNIDIRECTIONAL_ASSOCIATIONS
        );
        if (!$unidirectionalAssociations || !isset($unidirectionalAssociations[$associationName])) {
            // not unidirectional association
            return;
        }

        $targetEntityClass = $parentConfig->getField($associationName)->getTargetClass();
        $targetAssociationName = $unidirectionalAssociations[$associationName];
        $query = $this->doctrineHelper
            ->createQueryBuilder($targetEntityClass, 'e')
            ->innerJoin('e.' . $targetAssociationName, 'p')
            ->where('p = :' . AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME)
            ->setParameter(AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME, $context->getParentId());

        $context->setQuery($query);
    }
}
