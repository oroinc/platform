<?php

namespace Oro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\SearchBundle\Event\IndexerPrepareQueryEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class IndexerPrepareQueryListener
{
    const BUSINESS_UNIT_STRUCTURE_ORGANIZATION = 'business_units_search_handler';

    /** @var SecurityFacade */
    protected $securityFacade;

    public function __construct(
        SecurityFacade $securityFacade
    ) {
        $this->securityFacade = $securityFacade;
    }

    public function updateQuery(IndexerPrepareQueryEvent $event)
    {
        if ($event->getSearchHandlerState() === static::BUSINESS_UNIT_STRUCTURE_ORGANIZATION) {
            $user = $this->securityFacade->getLoggedUser();
            if ($user) {
                $organizations = $user->getOrganizations();

                $organizationsId = [];
                foreach ($organizations as $organization) {
                    $organizationsId[] = $organization->getId();
                }

                $query = $event->getQuery();
                $expr = $query->getCriteria()->expr();
                $query->getCriteria()->andWhere(
                    $expr->in('integer.organization', $organizationsId)
                );
            }
        }
    }
}
