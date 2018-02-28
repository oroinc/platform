<?php

namespace Oro\Bundle\SidebarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\User\UserInterface;

class WidgetRepository extends EntityRepository
{
    /**
     * @param UserInterface $user
     * @param string        $placement
     * @param Organization  $organization
     *
     * @return array
     */
    public function getWidgets($user, $placement, Organization $organization)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select(['wi.id', 'wi.placement', 'wi.position', 'wi.widgetName', 'wi.settings', 'wi.state'])
            ->from($this->_entityName, 'wi')
            ->where('wi.user = :user')
            ->andWhere('wi.placement = :placement')
            ->andWhere('wi.organization = :organization')
            ->setParameter('user', $user)
            ->setParameter('placement', $placement)
            ->setParameter('organization', $organization)
            ->orderBy('wi.position', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }
}
