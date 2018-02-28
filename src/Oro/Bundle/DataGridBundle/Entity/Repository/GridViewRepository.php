<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Security\Core\User\UserInterface;

class GridViewRepository extends EntityRepository
{
    /** @var array */
    private $defaultViewCache = [];

    /** @var array */
    private $viewsCache = [];

    /**
     * @param AclHelper     $aclHelper
     * @param UserInterface $user
     * @param string        $gridName
     *
     * @return AbstractGridView[]
     */
    public function findGridViews(AclHelper $aclHelper, UserInterface $user, $gridName)
    {
        $cacheKey = $this->getCacheKey($user, $gridName);
        if (!isset($this->viewsCache[$cacheKey])) {
            $qb = $this->createQueryBuilder('gv');
            $qb
                ->andWhere('gv.gridName = :gridName')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->eq('gv.' . $this->getOwnerFieldName(), ':owner'),
                        $qb->expr()->eq('gv.type', ':public')
                    )
                )
                ->setParameters(
                    [
                        'gridName' => $gridName,
                        'owner'    => $user,
                        'public'   => AbstractGridView::TYPE_PUBLIC
                    ]
                )
                ->orderBy('gv.gridName');

            $this->viewsCache[$cacheKey] = $aclHelper->apply($qb)->execute();
        }

        return $this->viewsCache[$cacheKey];
    }

    /**
     * @param AclHelper     $aclHelper
     * @param UserInterface $user
     * @param string        $gridName
     *
     * @return AbstractGridView|null
     */
    public function findDefaultGridView(AclHelper $aclHelper, UserInterface $user, $gridName)
    {
        $cacheKey = $this->getCacheKey($user, $gridName);
        if (!array_key_exists($cacheKey, $this->defaultViewCache)) {
            $qb = $this->getFindDefaultGridViewQb($user, $gridName);
            $qb->setMaxResults(1);
            $this->defaultViewCache[$cacheKey] = $aclHelper->apply($qb)->getOneOrNullResult();
        }

        return $this->defaultViewCache[$cacheKey];
    }

    /**
     * @param AclHelper $aclHelper
     * @param UserInterface $user
     * @param AbstractGridView $gridView
     * @param bool $checkOwner
     *
     * @return AbstractGridView[]
     */
    public function findDefaultGridViews(
        AclHelper $aclHelper,
        UserInterface $user,
        AbstractGridView $gridView,
        $checkOwner = true
    ) {
        /** @var AbstractGridView[] $defaultGridViews */
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
            ->where(
                $qb->expr()->eq('gv.gridName', ':gridName'),
                $qb->expr()->eq('u.' . $this->getUserFieldName(), ':user')
            );

        if ($checkOwner) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('gv.' . $this->getOwnerFieldName(), ':owner'),
                    $qb->expr()->eq('gv.type', ':public')
                )
            );

            $parameters = array_merge(
                $parameters,
                [
                    'owner'  => $user,
                    'public' => AbstractGridView::TYPE_PUBLIC
                ]
            );
        }

        $qb->setParameters($parameters);

        return $qb;
    }

    /**
     * @param UserInterface $user
     * @param string $gridName
     * @return string
     */
    protected function getCacheKey(UserInterface $user, $gridName)
    {
        return sprintf('%s.%s', $user->getUsername(), $gridName);
    }

    /**
     * @return string
     */
    protected function getOwnerFieldName()
    {
        return 'owner';
    }

    /**
     * @return string
     */
    protected function getUserFieldName()
    {
        return 'user';
    }
}
