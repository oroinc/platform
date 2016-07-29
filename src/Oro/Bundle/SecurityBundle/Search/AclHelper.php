<?php

namespace Oro\Bundle\SecurityBundle\Search;

use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class AclHelper
{
    /** @var SearchMappingProvider */
    protected $mappingProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var OwnershipConditionDataBuilder */
    protected $ownershipDataBuilder;

    /**
     * @param SearchMappingProvider         $mappingProvider
     * @param SecurityFacade                $securityFacade
     * @param OwnershipConditionDataBuilder $ownershipDataBuilder
     */
    public function __construct(
        SearchMappingProvider $mappingProvider,
        SecurityFacade $securityFacade,
        OwnershipConditionDataBuilder $ownershipDataBuilder
    ) {
        $this->securityFacade       = $securityFacade;
        $this->mappingProvider      = $mappingProvider;
        $this->ownershipDataBuilder = $ownershipDataBuilder;
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
        if (count($querySearchAliases) !== 0) {
            foreach ($querySearchAliases as $entityAlias) {
                $className = $this->mappingProvider->getEntityClass($entityAlias);
                if ($className) {
                    $ownerField = sprintf('%s_owner', $entityAlias);
                    $condition  = $this->ownershipDataBuilder->getAclConditionData($className, $permission);
                    if (count($condition) === 0 || !($condition[0] === null && $condition[3] === null)) {
                        $allowedAliases[] = $entityAlias;

                        // in case if we should not limit data for entity
                        if (count($condition) === 0 || $condition[1] === null) {
                            $ownerExpressions[] = $expr->gte('integer.' . $ownerField, SearchListener::EMPTY_OWNER_ID);

                            continue;
                        }

                        $owners = !empty($condition[1])
                            ? $condition[1]
                            : SearchListener::EMPTY_OWNER_ID;

                        $ownerExpressions[] = (!is_array($owners) ||  count($owners) === 1)
                            ? $expr->eq('integer.' . $ownerField, $owners)
                            : $expr->in('integer.' . $ownerField, $owners);
                    }
                }
            }
        }

        if (count($ownerExpressions) !== 0) {
            $query->getCriteria()->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $ownerExpressions));
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
        return $this->securityFacade->getOrganizationId();
    }

    /**
     * @param Query $query
     * @param $expr
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
