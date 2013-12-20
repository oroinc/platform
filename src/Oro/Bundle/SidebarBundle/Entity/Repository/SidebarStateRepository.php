<?php

namespace Oro\Bundle\SidebarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\SidebarBundle\Entity\SidebarState;
use Symfony\Component\Security\Core\User\UserInterface;

class SidebarStateRepository extends EntityRepository
{
    /**
     * @param UserInterface $user
     * @param string $position
     * @return SidebarState
     */
    public function getState($user, $position)
    {
        $qb = $this->createQueryBuilder('ss')
            ->where('ss.user = :user')
            ->andWhere('ss.position = :position')
            ->setParameter('user', $user)
            ->setParameter('position', $position);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
