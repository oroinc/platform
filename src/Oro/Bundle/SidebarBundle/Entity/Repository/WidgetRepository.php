<?php

namespace Oro\Bundle\SidebarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Symfony\Component\Security\Core\User\UserInterface;

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
