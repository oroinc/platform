<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultMenuUpdateProvider implements MenuUpdateProviderInterface
{
    /** @var SecurityFacade  */
    private $securityFacade;

    /** @var DoctrineHelper */
    private $doctrineHelper;

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
     * {@inheritdoc}
     */
    public function getUpdates($menu)
    {
        /** @var MenuUpdateRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroNavigationBundle:MenuUpdate');

        $organization = $this->getCurrentOrganization();
        $businessUnit = $this->getCurrentBusinessUnit($organization);
        $user = $this->getCurrentUser();

        $repository->getMenuUpdates($menu, $organization, $businessUnit, $user);
    }

    /**
     * @return null|Organization
     */
    private function getCurrentOrganization()
    {
        $organization = $this->securityFacade->getOrganization();
        if (!is_bool($organization)) {
            return $organization;
        }

        return null;
    }

    /**
     * @param Organization $organization
     *
     * @return null|BusinessUnit
     */
    private function getCurrentBusinessUnit(Organization $organization)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return null;
        }

        return $user->getBusinessUnits()
            ->filter(function (BusinessUnit $businessUnit) use ($organization) {
                return $businessUnit->getOrganization()->getId() === $organization->getId();
            })
            ->first();
    }

    /**
     * @return null|User
     */
    private function getCurrentUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }
}
