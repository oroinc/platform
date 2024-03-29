<?php

namespace Oro\Bundle\DashboardBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Dashboard entity repository
 */
class DashboardRepository extends EntityRepository
{
    /**
     * @param User $user
     * @return Dashboard|null
     */
    public function findUserActiveDashboard(User $user)
    {
        $activeDashboard = $this->getEntityManager()->getRepository(ActiveDashboard::class)
            ->findOneBy(array('user' => $user));

        return $activeDashboard ? $activeDashboard->getDashboard() : null;
    }

    /**
     * @param Organization $organization
     *
     * @return Dashboard|null
     */
    public function findDefaultDashboard(Organization $organization)
    {
        return $this->findOneBy(array('isDefault' => true, 'organization' => $organization));
    }
}
