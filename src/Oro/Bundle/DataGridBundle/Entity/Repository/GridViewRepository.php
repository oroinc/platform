<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param string $gridName
     * 
     * @return GridView[]
     */
    public function findGridViews(User $user, $gridName)
    {
        $qb = $this->createQueryBuilder('gv');

        return $qb
            ->andWhere('gv.gridName = :gridName')
            ->andWhere($qb->expr()->orx(
                'gv.type = :publicType',
                $qb->expr()->andX(
                    'gv.type = :privateType',
                    'gv.owner = :owner')))
            ->setParameters([
                'owner' => $user,
                'gridName' => $gridName,
                'publicType' => GridView::TYPE_PUBLIC,
                'privateType' => GridView::TYPE_PRIVATE,
            ])
            ->orderBy('gv.gridName')
            ->getQuery()
            ->getResult();
    }
}
