<?php

namespace Oro\Bundle\DataGridBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridViewUser;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Security\Core\User\UserInterface;

class GridViewUserRepository extends EntityRepository
{
    /**
     * @param AclHelper     $aclHelper
     * @param UserInterface $user
     * @param string        $gridName
     *
     * @return AbstractGridViewUser|null
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
     * @return AbstractGridViewUser[]
     */
    public function findDefaultGridViews(
        AclHelper $aclHelper,
        UserInterface $user,
        $gridName
    ) {
        /** @var AbstractGridViewUser[] $defaultGridViews */
        $qb = $this->getFindDefaultGridViewQb($user, $gridName);

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * @param ViewInterface $view
     * @param UserInterface $user
     * @return AbstractGridViewUser
     */
    public function findByGridViewAndUser(ViewInterface $view, UserInterface $user)
    {
        return $this->findOneBy(
            [
                $this->getUserFieldName() => $user,
                'alias' => $view->getName(),
                'gridName' => $view->getGridName()
            ]
        );
    }

    /**
     * @param UserInterface $user
     * @param string        $gridName
     *
     * @return QueryBuilder
     */
    protected function getFindDefaultGridViewQb(UserInterface $user, $gridName)
    {
        $qb = $this->createQueryBuilder('gvu');
        $qb->where(
            $qb->expr()->eq('gvu.' . $this->getUserFieldName(), ':user'),
            $qb->expr()->eq('gvu.gridName', ':gridName')
        )
        ->setParameters(
            [
                'user' => $user,
                'gridName' => $gridName
            ]
        );

        return $qb;
    }

    /**
     * @return string
     */
    protected function getUserFieldName()
    {
        return 'user';
    }
}
