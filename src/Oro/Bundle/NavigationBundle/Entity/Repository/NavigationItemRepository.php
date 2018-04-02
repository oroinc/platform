<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * NavigationItem Repository
 */
class NavigationItemRepository extends EntityRepository implements NavigationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems($user, Organization $organization, $type = null, $options = array())
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select(
            'ni.id',
            'ni.url',
            'ni.title',
            'ni.type'
        )
        ->from($this->_entityName, 'ni')
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
}
