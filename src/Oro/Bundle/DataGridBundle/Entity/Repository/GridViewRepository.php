<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

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
     * @param string $gridName
     * @param User $user
     *
     * @return GridView|null
     */
    public function findDefaultGridView($gridName, User $user)
    {
        $qb = $this->createQueryBuilder('gv')
            ->join('gv.users', 'user')
            ->where('gv.gridName = :gridName')
            ->andWhere('user = :user')
            ->setParameters(
                [
                    'gridName' => $gridName,
                    'user'     => $user,
                ]
            );

        return $qb->getQuery()->getOneOrNullResult();
    }
}
