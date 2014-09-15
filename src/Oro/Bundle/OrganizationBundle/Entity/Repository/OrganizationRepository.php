<?php
namespace Oro\Bundle\OrganizationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class OrganizationRepository extends EntityRepository
{
    /**
     * Finds the first record
     *
     * @return Organization
     */
    public function getFirst()
    {
        return $this->createQueryBuilder('org')
            ->select('org')
            ->orderBy('org.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
    }

    /**
     * Get organization by id
     *
     * @param $id
     * @return Organization
     */
    public function getOrganizationById($id)
    {
        return $this->createQueryBuilder('org')
            ->select('org')
            ->where('org.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Get organization by name
     *
     * @param string $name
     * @return Organization
     */
    public function getOrganizationByName($name)
    {
        return $this->createQueryBuilder('org')
            ->select('org')
            ->where('org.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Returns enabled organizations
     *
     * @param bool $asArray
     * @return Organization[]|array
     */
    public function getEnabled($asArray = false)
    {
        $organizationsQuery = $this->createQueryBuilder('org')
            ->select('org')
            ->where('org.enabled = true')
            ->getQuery();

        if ($asArray) {
            return $organizationsQuery->getArrayResult();
        }

        return $organizationsQuery->getResult();
    }

    /**
     * Update all records in given table with organization id
     *
     * @param string  $tableName    table name to update, example: OroCRMAccountBundle:Account or OroUserBundle:Group
     * @param integer $id           Organization id
     * @param string  $relationName relation name to update. By default 'organization'
     *
     * @return integer Number of rows affected
     */
    public function updateWithOrganization($tableName, $id, $relationName = 'organization')
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->update($tableName, 't')
            ->set('t.' . $relationName, ':id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Returns user organizations by name
     *
     * @param User   $user
     * @param string $name
     * @param bool   $useLikeExpr Using expr()->like by default and expr()->eq otherwise
     * @param bool   $singleResult If we expected only one result
     *
     * @return Organization[]
     */
    public function getEnabledByUserAndName(User $user, $name, $useLikeExpr = true, $singleResult = false)
    {
        $qb = $this->createQueryBuilder('org');
        $qb->select('org')
            ->join('org.users', 'user')
            ->where('org.enabled = true')
            ->andWhere('user.id = :user')
            ->setParameter('user', $user);

        if ($useLikeExpr) {
            $qb->andWhere($qb->expr()->like('org.name', ':orgName'))
                ->setParameter('orgName', '%' . str_replace(' ', '%', $name) . '%');
        } else {
            $qb->andWhere($qb->expr()->eq('org.name', ':orgName'))
                ->setParameter('orgName', $name);
        }

        $query = $qb->getQuery();

        return $singleResult ? $query->getOneOrNullResult() : $query->getResult();
    }

    /**
     * Get user organization by id
     *
     * @param User    $user
     * @param integer $id
     * @return Organization
     */
    public function getEnabledUserOrganizationById(User $user, $id)
    {
        return $user->getOrganizations()->filter(
            function (Organization $item) use ($id) {
                return $item->getId() == $id && $item->isEnabled();
            }
        );
    }
}
