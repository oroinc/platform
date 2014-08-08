<?php
namespace Oro\Bundle\OrganizationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationRepository extends EntityRepository
{
    /**
     * Finds the first record
     *
     * @return Organization
     */
    public function getFirst()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT org FROM OroOrganizationBundle:Organization org ORDER BY org.id')
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
        return $this->getEntityManager()
            ->createQuery('SELECT org FROM OroOrganizationBundle:Organization org WHERE org.id = :id')
            ->setParameter('id', $id)
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
        return $this->getEntityManager()
            ->createQuery('SELECT org FROM OroOrganizationBundle:Organization org WHERE org.name = :name')
            ->setParameter('name', $name)
            ->getSingleResult();
    }

    /**
     * Returns array of enabled organizations
     *
     * @param bool $asArray
     * @return array
     */
    public function getEnabled($asArray = false)
    {
        $organizationsQuery = $this->getEntityManager()
            ->createQuery('SELECT org FROM OroOrganizationBundle:Organization org WHERE org.enabled = 1');

        if ($asArray) {
            return $organizationsQuery->getArrayResult();
        }

        return $organizationsQuery->getResult();
    }

    /**
     * Update all records in given table with organization id
     *
     * @param string  $tableName table name to update, example: OroCRMAccountBundle:Account or OroUserBundle:Group
     * @param integer $id        Organization id
     *
     * @return integer Number of rows affected
     */
    public function updateWithOrganization($tableName, $id)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->update($tableName, 't')
            ->set('t.organization', ':id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
