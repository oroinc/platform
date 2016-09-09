<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultMenuUpdateProvider extends AbstractMenuUpdateProvider
{
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

        return $repository->getMenuUpdates($menu, $organization, $businessUnit, $user);
    }

    /**
     * @param Organization $organization
     *
     * @return null|BusinessUnit
     */
    private function getCurrentBusinessUnit(Organization $organization)
    {
        $user = $this->getCurrentUser();
        if (!$user || !$organization) {
            return null;
        }

        $businessUnit = $user->getBusinessUnits()
            ->filter(function (BusinessUnit $businessUnit) use ($organization) {
                return $businessUnit->getOrganization()->getId() === $organization->getId();
            })
            ->first();

        return !is_bool($businessUnit) ? $businessUnit : null;
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
