<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;

abstract class AbstractMenuUpdateProvider implements MenuUpdateProviderInterface
{
    /** @var SecurityFacade  */
    protected $securityFacade;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param SecurityFacade $securityFacade
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper
    ) {
        $this->securityFacade = $securityFacade;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return null|Organization
     */
    protected function getCurrentOrganization()
    {
        $organization = $this->securityFacade->getOrganization();
        if (!is_bool($organization)) {
            return $organization;
        }

        return null;
    }
}
