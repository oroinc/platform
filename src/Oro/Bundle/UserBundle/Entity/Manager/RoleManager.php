<?php

namespace Oro\Bundle\UserBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * Manager for the Role entity.
 */
class RoleManager
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function getUserQueryBuilder(Role $role): QueryBuilder
    {
        return $this->getRoleRepo()->getUserQueryBuilder($role);
    }

    private function getRoleRepo(): RoleRepository
    {
        return $this->doctrine->getRepository(Role::class);
    }
}
