<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class GridViewRepository extends EntityRepository
{
    /** @var array */
    protected $cache = [];

    /**
     * @param AclHelper     $aclHelper
     * @param UserInterface $user
     * @param string        $gridName
     *
     * @return GridView[]
     */
    public function findGridViews(AclHelper $aclHelper, UserInterface $user, $gridName)
    {
        $cacheKey = sprintf('%s.%s', $user->getUsername(), $gridName);
        if (!isset($this->cache[$cacheKey])) {
            $qb = $this->createQueryBuilder('gv');
            $qb
                ->andWhere('gv.gridName = :gridName')
                ->andWhere(
                    $qb->expr()->orX(
                        'gv.owner = :owner',
                        'gv.type = :public'
                    )
                )
                ->setParameters(
                    [
                        'gridName' => $gridName,
                        'owner'    => $user,
                        'public'   => GridView::TYPE_PUBLIC,
                    ]
                )
                ->orderBy('gv.gridName');

            $this->cache[$cacheKey] = $aclHelper->apply($qb)->getResult();
        }

        return $this->cache[$cacheKey];
    }

    /**
     * @param AclHelper     $aclHelper
     * @param UserInterface $user
     * @param string        $gridName
     *
     * @return GridView|null
     */
    public function findDefaultGridView(AclHelper $aclHelper, UserInterface $user, $gridName)
    {
        $qb = $this->getFindDefaultGridViewQb($user, $gridName);
        $qb->setMaxResults(1);

        return $aclHelper->apply($qb)->getOneOrNullResult();
    }

    /**
     * @param AclHelper     $aclHelper
     * @param UserInterface $user
     * @param GridView      $gridView
     * @param bool          $checkOwner
     *
     * @return GridView[]
     */
    public function findDefaultGridViews(
        AclHelper $aclHelper,
        UserInterface $user,
        GridView $gridView,
        $checkOwner = true
    ) {
        /** @var GridView[] $defaultGridViews */
        $qb = $this->getFindDefaultGridViewQb($user, $gridView->getGridName(), $checkOwner);

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * @param UserInterface $user
     * @param string        $gridName
     * @param bool          $checkOwner
     *
     * @return QueryBuilder
     */
    protected function getFindDefaultGridViewQb(UserInterface $user, $gridName, $checkOwner = true)
    {
        $parameters = [
            'gridName' => $gridName,
            'user'     => $user,
        ];

        $qb = $this->createQueryBuilder('gv');
        $qb->innerJoin('gv.users', 'u')
            ->where('gv.gridName = :gridName')
            ->andWhere('u.user = :user');

        if ($checkOwner) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'gv.owner = :owner',
                    'gv.type = :public'
                )
            );

            $parameters = array_merge(
                $parameters,
                [
                    'owner'  => $user,
                    'public' => GridView::TYPE_PUBLIC
                ]
            );
        }

        $qb->setParameters($parameters);

        return $qb;
    }
}
