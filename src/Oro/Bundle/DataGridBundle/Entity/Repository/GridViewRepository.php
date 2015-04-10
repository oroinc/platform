<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class GridViewRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @param User $user
     * @param string $gridName
     *
     * @return GridView[]
     */
    public function findGridViews(AclHelper $aclHelper, $gridName)
    {
        $qb = $this->createQueryBuilder('gv');
        $qb
            ->andWhere('gv.gridName = :gridName')
            ->andWhere($qb->expr()->orx(
                'gv.type = :publicType',
                $qb->expr()->andX(
                    'gv.type = :privateType'
                )
            ))
            ->setParameters([
                'gridName' => $gridName,
                'publicType' => GridView::TYPE_PUBLIC,
                'privateType' => GridView::TYPE_PRIVATE,
            ])
            ->orderBy('gv.gridName');

        $aclHelper->apply($qb);

        return $qb
            ->getQuery()
            ->getResult();
    }
}
