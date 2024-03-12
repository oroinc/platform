<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Group entity repository
 */
class GroupRepository extends EntityRepository
{
    /**
     * Get user query builder
     *
     * @param  Group        $group
     * @return QueryBuilder
     */
    public function getUserQueryBuilder(Group $group)
    {
        return $this->_em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->join('u.groups', 'groups')
            ->where('groups = :group')
            ->setParameter('group', $group);
    }
}
