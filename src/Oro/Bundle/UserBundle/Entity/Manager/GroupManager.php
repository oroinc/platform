<?php

namespace Oro\Bundle\UserBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Repository\GroupRepository;

/**
 * Manager for the Group entity.
 */
class GroupManager
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function getUserQueryBuilder(Group $group): QueryBuilder
    {
        return $this->getGroupRepo()->getUserQueryBuilder($group);
    }

    private function getGroupRepo(): GroupRepository
    {
        return $this->doctrine->getRepository(Group::class);
    }
}
