<?php

namespace Oro\Bundle\MigrationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class DataFixtureRepository extends EntityRepository
{
    /**
     * @param string $where
     * @param array  $parameters
     *
     * @return bool
     */
    public function isDataFixtureExists($where, array $parameters = [])
    {
        $entityId = $this->createQueryBuilder('m')
            ->select('m.id')
            ->where($where)
            ->setMaxResults(1)
            ->getQuery()
            ->execute($parameters);

        return $entityId ? true : false;
    }

    /**
     * Update data fixture history
     *
     * @param array  $updateFields assoc array with field names and values that should be updated
     * @param string $where        condition
     * @param array  $parameters   optional parameters for where condition
     */
    public function updateDataFixutreHistory(array $updateFields, $where, array $parameters = [])
    {
        $qb = $this->_em
            ->createQueryBuilder()
            ->update('OroMigrationBundle:DataFixture', 'm')
            ->where($where);

        foreach ($updateFields as $fieldName => $fieldValue) {
            $qb->set($fieldName, $fieldValue);
        }
        $qb->getQuery()->execute($parameters);
    }
}
