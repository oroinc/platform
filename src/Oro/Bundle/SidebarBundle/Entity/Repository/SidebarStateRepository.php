<?php

namespace Oro\Bundle\SidebarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Symfony\Component\Security\Core\User\UserInterface;

class SidebarStateRepository extends EntityRepository
{
    /**
     * @param UserInterface $user
     * @param string $position
     * @return array
     */
    public function getState($user, $position)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select(['ss.id', 'ss.position', 'ss.state'])
            ->from($this->_entityName, 'ss')
            ->where('ss.user = :user')
            ->andWhere('ss.position = :position')
            ->setParameter('user', $user)
            ->setParameter('position', $position);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
