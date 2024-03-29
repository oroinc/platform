<?php

namespace Oro\Bundle\UserBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Repository\GroupRepository;

/**
 * Manager for the Group Entity
 */
class GroupManager
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get user query builder
     *
     * @param Group $group
     * @return QueryBuilder
     */
    public function getUserQueryBuilder(Group $group)
    {
        return $this->getGroupRepo()->getUserQueryBuilder($group);
    }

    /**
     * @return GroupRepository
     */
    protected function getGroupRepo()
    {
        return $this->em->getRepository(Group::class);
    }
}
