<?php

namespace Oro\Bundle\SidebarBundle\Entity\Repository;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class WidgetRepository extends EntityRepository
{
    /**
     * @param UserInterface $user
     * @param string        $placement
     * @param Organization  $organization
     *
     * @return array
     */
    public function getWidgets($user, $placement, Organization $organization)
    {
        $qb = $this->createQueryBuilder('wi')
            ->where('wi.user = :user')
            ->andWhere('wi.placement = :placement')
            ->andWhere('wi.organization = :organization')
            ->setParameter('user', $user)
            ->setParameter('placement', $placement)
            ->setParameter('organization', $organization)
            ->orderBy('wi.position', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }
}
