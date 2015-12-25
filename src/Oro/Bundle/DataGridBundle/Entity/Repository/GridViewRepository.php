<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class GridViewRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @param UserInterface $user
     * @param string $gridName
     *
     * @return GridView[]
     */
    public function findGridViews(AclHelper $aclHelper, UserInterface $user, $gridName)
    {
        $qb = $this->createQueryBuilder('gv');
        $qb
            ->andWhere('gv.gridName = :gridName')
            ->andWhere($qb->expr()->orX(
                'gv.owner = :owner',
                'gv.type = :public'
            ))
            ->setParameters([
                'gridName' => $gridName,
                'owner'    => $user,
                'public'   => GridView::TYPE_PUBLIC,
            ])
            ->orderBy('gv.gridName');

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * @param AclHelper     $aclHelper
     * @param UserInterface $user
     * @param               $gridName
     *
     * @return mixed
     */
    public function findUserDefaultGridView(AclHelper $aclHelper, UserInterface $user, $gridName)
    {
        $qb = $this->createQueryBuilder('gv');
        $qb->innerJoin('gv.users', 'u')
            ->where('gv.gridName = :gridName')
            ->andWhere('u = :user')
            ->setParameters([
                'gridName' => $gridName,
                'user'    => $user
            ])
            ->setMaxResults(1);

        return $aclHelper->apply($qb)->getOneOrNullResult();
    }
}
