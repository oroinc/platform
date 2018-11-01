<?php

namespace Oro\Bundle\SecurityBundle\Search;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Applies ACL check to search index queries
 */
class AclHelper
{
    /** @var SearchMappingProvider */
    protected $mappingProvider;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var AclConditionDataBuilderInterface */
    protected $ownershipDataBuilder;

    /** @var OwnershipMetadataProviderInterface */
    protected $metadataProvider;

    /**
     * @param SearchMappingProvider         $mappingProvider
     * @param TokenAccessorInterface        $tokenAccessor
     * @param AclConditionDataBuilderInterface $ownershipDataBuilder
     * @param OwnershipMetadataProviderInterface $metadataProvider
     */
    public function __construct(
        SearchMappingProvider $mappingProvider,
        TokenAccessorInterface $tokenAccessor,
        AclConditionDataBuilderInterface $ownershipDataBuilder,
        OwnershipMetadataProviderInterface $metadataProvider
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->mappingProvider = $mappingProvider;
        $this->ownershipDataBuilder = $ownershipDataBuilder;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * Applies ACL conditions to the search query
     *
     * @param Query  $query
     * @param string $permission
     *
     * @return Query
     */
    public function apply(Query $query, $permission = 'VIEW')
    {
        $querySearchAliases = $this->getSearchAliases($query);

        $allowedAliases   = [];
        $ownerExpressions = [];
        $expr             = $query->getCriteria()->expr();
        if (false !== $querySearchAliases && count($querySearchAliases) !== 0) {
            foreach ($querySearchAliases as $entityAlias) {
                $className = $this->mappingProvider->getEntityClass($entityAlias);
                if ($className) {
                    $metadata = $this->metadataProvider->getMetadata($className);
                    $ownerField = sprintf('%s_%s', $entityAlias, $metadata->getOwnerFieldName());

                    $condition  = $this->ownershipDataBuilder->getAclConditionData($className, $permission);
                    if (count($condition) === 0 || !($condition[0] === null && $condition[2] === null)) {
                        $allowedAliases[] = $entityAlias;

                        // in case if we should not limit data for entity
                        if (count($condition) === 0 || $condition[1] === null) {
                            $ownerExpressions[] = $expr->gte('integer.' . $ownerField, SearchListener::EMPTY_OWNER_ID);

                            continue;
                        }

                        $owners = !empty($condition[1])
                            ? $condition[1]
                            : SearchListener::EMPTY_OWNER_ID;

                        if (is_array($owners)) {
                            $ownerExpressions[] = count($owners) == 1
                                ? $expr->eq('integer.' . $ownerField, reset($owners))
                                : $expr->in('integer.' . $ownerField, $owners);
                        } else {
                            $ownerExpressions[] = $expr->eq('integer.' . $ownerField, $owners);
                        }
                    }
                }
            }
        }

        if (count($ownerExpressions) !== 0) {
            $orExpression = new CompositeExpression(CompositeExpression::TYPE_OR, $ownerExpressions);
            $query->getCriteria()->andWhere($orExpression);
        }
        $query->from($allowedAliases);

        $this->addOrganizationLimits($query, $expr);

        return $query;
    }

    /**
     * @return int|null
     */
    protected function getOrganizationId()
    {
        return $this->tokenAccessor->getOrganizationId();
    }

    /**
     * @param Query $query
     * @param ExpressionBuilder $expr
     */
    protected function addOrganizationLimits(Query $query, $expr)
    {
        $organizationId = $this->getOrganizationId();
        if ($organizationId) {
            $query->getCriteria()->andWhere(
                $expr->in('integer.organization', [$organizationId, SearchListener::EMPTY_ORGANIZATION_ID])
            );
        }
    }

    /**
     * Get search query 'from' aliases
     *
     * @param Query $query
     *
     * @return array Return search aliases from Query. In case if from part = *, return all search aliases
     */
    protected function getSearchAliases(Query $query)
    {
        $queryAliases = $query->getFrom();

        if ($queryAliases[0] === '*') {
            $queryAliases = $this->mappingProvider->getEntitiesListAliases();
        }

        return $queryAliases;
    }
}
