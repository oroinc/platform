<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class MenuUpdateRepository extends EntityRepository
{
    /**
     * @param string  $menu
     * @param string  $ownershipType
     * @param integer $ownerId
     * @return \Oro\Bundle\NavigationBundle\Entity\MenuUpdate[]
     *
     */
    public function getMenuUpdates($menu, $ownershipType, $ownerId)
    {
        $qb = $this->createQueryBuilder('mu');

        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq('mu.menu', ':menu'),
                $qb->expr()->eq('mu.ownershipType', ':ownershipType'),
                $qb->expr()->eq('mu.ownerId', ':ownerId')
            )
        )->setParameters(
            [
                'menu' => $menu,
                'ownershipType' => $ownershipType,
                'ownerId' => $ownerId,
            ]
        );

        return $qb
            ->getQuery()
            ->getResult();
    }
}
