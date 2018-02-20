<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * PinbarTab Repository
 */
class PinbarTabRepository extends EntityRepository implements NavigationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems($user, Organization $organization, $type = null, $options = array())
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select(
            'pt.id',
            'ni.url',
            'ni.title',
            'ni.type',
            'ni.id AS parent_id',
            'pt.maximized'
        )
        ->from($this->_entityName, 'pt')
        ->innerJoin('pt.item', 'ni', Expr\Join::WITH)
        ->where(
            $qb->expr()->andX(
                $qb->expr()->eq('ni.user', ':user'),
                $qb->expr()->eq('ni.type', ':type'),
                $qb->expr()->eq('ni.organization', ':organization')
            )
        )
        ->orderBy('ni.position', 'ASC')
        ->setParameters(['user' => $user, 'type' => $type, 'organization' => $organization]);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Increment positions of Pinbar tabs for specified user
     *
     * @param User|integer $user
     * @param int          $navigationItemId
     * @param Organization $organization
     *
     * @return mixed
     */
    public function incrementTabsPositions($user, $navigationItemId, Organization $organization)
    {
        $qb = $this->_em->createQueryBuilder();

        return $qb->update($this->getNavigationItemClassName(), 'p')
            ->set('p.position', 'p.position + 1')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->neq('p.id', ':item_id'),
                    $qb->expr()->eq('p.type', ':type'),
                    $qb->expr()->eq('p.user', ':user'),
                    $qb->expr()->eq('p.organization', ':organization')
                )
            )
            ->setParameter('item_id', $navigationItemId, \PDO::PARAM_INT)
            ->setParameter('type', 'frontend_pinbar', \PDO::PARAM_STR)
            ->setParameter('user', $user)
            ->setParameter('organization', $organization)
            ->getQuery()
            ->execute();
    }

    /**
     * @return string
     */
    protected function getNavigationItemClassName()
    {
        return 'Oro\Bundle\NavigationBundle\Entity\NavigationItem';
    }
}
