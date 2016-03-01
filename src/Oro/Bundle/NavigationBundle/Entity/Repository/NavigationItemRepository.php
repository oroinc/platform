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

        $qb->add(
            'select',
            new Expr\Select(
                array(
                    'ni.id',
                    'ni.url',
                    'ni.title',
                    'ni.type'
                )
            )
        )
        ->add('from', new Expr\From($this->_entityName, 'ni'))
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
}
