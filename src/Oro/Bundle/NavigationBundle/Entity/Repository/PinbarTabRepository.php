<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

/**
 * PinbarTab Repository
 */
class PinbarTabRepository extends EntityRepository implements NavigationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems($user, $organization, $type, $options = array())
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->add(
            'select',
            new Expr\Select(
                array(
                    'pt.id',
                    'ni.url',
                    'ni.title',
                    'ni.type',
                    'ni.id AS parent_id',
                    'pt.maximized'
                )
            )
        )
        ->add('from', new Expr\From('Oro\Bundle\NavigationBundle\Entity\PinbarTab', 'pt'))
        ->innerJoin('pt.item', 'ni', Expr\Join::WITH)
        ->add(
            'where',
            $qb->expr()->andx(
                $qb->expr()->eq('ni.user', ':user'),
                $qb->expr()->eq('ni.type', ':type'),
                $qb->expr()->eq('ni.organization', ':organization')
            )
        )
        ->add('orderBy', new Expr\OrderBy('ni.position', 'ASC'))
        ->setParameters(array('user' => $user, 'type' => $type, 'organization' => $organization));

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Increment positions of Pinbar tabs for specified user
     *
     * @param  \Oro\Bundle\UserBundle\Entity\User                $user
     * @param  int                                               $navigationItemId
     * @param \Oro\Bundle\OrganizationBundle\Entity\Organization $organization
     *
     * @return mixed
     */
    public function incrementTabsPositions($user, $navigationItemId, $organization)
    {
        $updateQuery = $this->_em->createQuery(
            'UPDATE Oro\Bundle\NavigationBundle\Entity\NavigationItem p '
            . 'set p.position = p.position + 1 '
            . 'WHERE p.id != ' . (int) $navigationItemId
            . " AND p.type = 'pinbar'"
            . " AND p.user = :user"
            . " AND p.organization = :organization"
        );
        $updateQuery->setParameter('user', $user);
        $updateQuery->setParameter('organization', $organization);

        return $updateQuery->execute();
    }
}
