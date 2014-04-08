<?php
namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\UserBundle\Entity\Role;

class RoleRepository extends EntityRepository
{
    /**
     * Returns a query builder which can be used to get a list of users assigned to the given role
     *
     * @param  Role $role
     * @return QueryBuilder
     */
    public function getUserQueryBuilder(Role $role)
    {
        return $this->_em->createQueryBuilder()
            ->select('u')
            ->from('OroUserBundle:User', 'u')
            ->join('u.roles', 'role')
            ->where('role = :role')
            ->setParameter('role', $role);
    }

    /**
     * Checks if there are at least one user assigned to the given role
     *
     * @param Role $role
     * @return bool
     */
    public function hasAssignedUsers(Role $role)
    {
        $findResult = $this->getUserQueryBuilder($role)
            ->select('role.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    /**
     * @param Role $role
     * @return User
     */
    public function getFirstMatchedUser(Role $role)
    {
        return $this
            ->getUserQueryBuilder($role)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
