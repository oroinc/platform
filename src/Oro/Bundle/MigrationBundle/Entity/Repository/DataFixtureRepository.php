<?php

namespace Oro\Bundle\MigrationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\MigrationBundle\Entity\DataFixture;

/**
 * Doctrine repository for DataFixture entity.
 *
 * @deprecated Will be removed in version 4.2
 */
class DataFixtureRepository extends EntityRepository
{
    /**
     * @param string|string[] $className
     *
     * @return DataFixture[]
     *
     * @deprecated Will be removed in version 4.2, use the "findBy" method instead
     */
    public function findByClassName($className)
    {
        @trigger_error(sprintf(
            'The "%s()" method is deprecated and will be removed in version 4.2',
            __METHOD__
        ), E_USER_DEPRECATED);

        return $this->findBy(['className' => $className]);
    }

    /**
     * @param string $where
     * @param array  $parameters
     *
     * @return bool
     *
     * @deprecated Will be removed in version 4.2
     */
    public function isDataFixtureExists($where, array $parameters = [])
    {
        @trigger_error(sprintf(
            'The "%s()" method is deprecated and will be removed in version 4.2',
            __METHOD__
        ), E_USER_DEPRECATED);

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
     *
     * @deprecated Will be removed in version 4.2
     * @see \Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface
     */
    public function updateDataFixutreHistory(array $updateFields, $where, array $parameters = [])
    {
        @trigger_error(sprintf(
            'The "%s()" method is deprecated and will be removed in version 4.2'
            . ', use the "%s" interface for rename data fixtures.',
            __METHOD__,
            \Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface::class
        ), E_USER_DEPRECATED);

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
