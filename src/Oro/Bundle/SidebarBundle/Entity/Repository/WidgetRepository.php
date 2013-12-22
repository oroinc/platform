<?php

namespace Oro\Bundle\SidebarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Symfony\Component\Security\Core\User\UserInterface;

class WidgetRepository extends EntityRepository
{
    /**
     * @param UserInterface $user
     * @param string $placement
     * @return array
     */
    public function getWidgets($user, $placement)
    {
        $qb = $this->createQueryBuilder('wi')
            ->where('wi.user = :user')
            ->andWhere('wi.placement = :placement')
            ->setParameter('user', $user)
            ->setParameter('placement', $placement)
            ->orderBy('wi.position', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }
}
