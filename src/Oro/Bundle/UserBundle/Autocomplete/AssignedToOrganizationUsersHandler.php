<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Search handler to search users with limitation by assigned to organizations.
 * It does not use any common ACL checks.
 */
class AssignedToOrganizationUsersHandler extends UserSearchHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIds($search, $firstResult, $maxResults)
    {
        $searchQuery = $this->indexer->getSimpleSearchQuery(
            $search,
            $firstResult,
            $maxResults,
            $this->entitySearchAlias
        );
        $this->addOrganizationLimits($searchQuery);

        // turn off ACL checks for search queries
        $this->indexer->setIsAllowedApplyAcl(false);
        $result = $this->indexer->query($searchQuery);
        // restore ACL checks for search queries
        $this->indexer->setIsAllowedApplyAcl(true);

        $elements = $result->getElements();

        $ids = [];
        foreach ($elements as $element) {
            $ids[] = $element->getRecordId();
        }

        return $ids;
    }

    /**
     * Apply limitation by current organization
     *
     * @param Query $query
     */
    protected function addOrganizationLimits(Query $query)
    {
        $expr = $query->getCriteria()->expr();
        $organizationId =  $this->securityFacade->getOrganizationId();
        if ($organizationId) {
            $query->getCriteria()->andWhere(
                $expr->eq('integer.assigned_organization_id', $organizationId)
            );
        }
    }
}
