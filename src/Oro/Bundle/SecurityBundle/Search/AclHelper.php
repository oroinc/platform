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
        $queryFromEntities = $query->getFrom();

        // in query, from record !== '*'
        if ($queryFromEntities[0] === '*') {
            $queryFromEntities = $this->mappingProvider->getEntitiesListAliases();
        }

        $allowedAliases   = [];
        $ownerExpressions = [];
        $expr             = $query->getCriteria()->expr();
        if (!empty($queryFromEntities)) {
            foreach ($queryFromEntities as $entityAlias) {
                $className = $this->mappingProvider->getEntityClass($entityAlias);
                if ($className) {
                    $condition = $this->ownershipDataBuilder->getAclConditionData($className, $permission);
                    if ($condition !== null) {
                        $allowedAliases[] = $entityAlias;
                        $ownerField       = sprintf('%s_owner', $entityAlias);

                        // if condition is empty array or id's are null,
                        // this means what we should not limit this entity with owners
                        if (empty($condition) || $condition[1] === null) {
                            $ownerExpressions[] = $expr->gt('integer.' . $ownerField, SearchListener::EMPTY_OWNER_ID);

                            continue;
                        }

                        $owners = [SearchListener::EMPTY_OWNER_ID];
                        if (!empty($condition[1])) {
                            $owners = $condition[1];
                        }
                        if (is_array($owners)) {
                            $ownerExpressions[] = $expr->in('integer.' . $ownerField, $owners);
                        } else {
                            $ownerExpressions[] = $expr->eq('integer.' . $ownerField, $owners);
                        }
                    }
                }
            }

        }
        if (!empty($ownerExpressions)) {
            $query->getCriteria()->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $ownerExpressions));
        }
        $query->from($allowedAliases);

        // add organization limitation
        $organizationId = $this->securityFacade->getOrganizationId();
        if ($organizationId) {
            $query->getCriteria()->andWhere(
                $expr->in('integer.organization', [$organizationId, 0])
            );
        }

        return $query;
    }
}
