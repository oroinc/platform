<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class GridViewUserRepository extends EntityRepository
{
    /**
     * @param AclHelper     $aclHelper
     * @param UserInterface $user
     * @param string        $gridName
     *
     * @return GridViewUser|null
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
     * @param string        $gridName
     *
     * @return GridViewUser[]
     */
    public function findDefaultGridViews(
        AclHelper $aclHelper,
        UserInterface $user,
        $gridName
    ) {
        /** @var GridViewUser[] $defaultGridViews */
        $qb = $this->getFindDefaultGridViewQb($user, $gridName);

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * @param UserInterface $user
     * @param string        $gridName
     *
     * @return QueryBuilder
     */
    protected function getFindDefaultGridViewQb(UserInterface $user, $gridName)
    {
        $parameters = [
            'gridName' => $gridName,
            'user'     => $user,
        ];

        $qb = $this->createQueryBuilder('gvu');
        $qb->where('gvu.user = :user')
            ->andWhere('gvu.gridName = :gridName');

        $qb->setParameters($parameters);
        $qb->getQuery()->getSql();
        return $qb;
    }
}
