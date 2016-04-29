<?php

namespace Oro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class BusinessUnitAutocompleteListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    public function onSearchBefore(BeforeSearchEvent $event)
    {
        $query = $event->getQuery();
        $from  = $query->getFrom();

        if (in_array('oro_business_unit', $from, true)) {
            $criteria = $query->getCriteria();
            $expr = $criteria->expr();
            $criteria->where($expr->eq('integer.organization', $this->securityFacade->getOrganizationId()));
        }
    }
}
