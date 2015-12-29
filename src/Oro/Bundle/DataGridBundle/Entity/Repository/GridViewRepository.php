<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
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
     * @param User   $user
     * @param string $gridName
     *
     * @return GridView|null
     */
    public function findDefaultGridView(User $user, $gridName)
    {
        $qb = $this->getFindDefaultGridViewQb($user, $gridName);

        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /**
     * @param User     $user
     * @param GridView $gridView
     *
     * @return GridView[]
     */
    public function findDefaultGridViews(User $user, GridView $gridView)
    {
        /** @var GridView[] $defaultGridViews */
        $defaultGridViews = $this
            ->getFindDefaultGridViewQb($user, $gridView->getGridName())
            ->getQuery()
            ->getResult();

        return $defaultGridViews;
    }

    /**
     * @param User   $user
     * @param string $gridName
     *
     * @return QueryBuilder
     */
    protected function getFindDefaultGridViewQb(User $user, $gridName)
    {
        $qb = $this->createQueryBuilder('gv');
        $qb->innerJoin('gv.users', 'u')
            ->where('gv.gridName = :gridName')
            ->andWhere('u = :user')
            ->setParameters([
                'gridName' => $gridName,
                'user'     => $user
            ]);

        return $qb;
    }
}
