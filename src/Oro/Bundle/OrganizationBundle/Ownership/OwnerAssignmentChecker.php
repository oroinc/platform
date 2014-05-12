<?php

namespace Oro\Bundle\OrganizationBundle\Ownership;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Default implementation of check owner assignment algorithm
 */
class OwnerAssignmentChecker implements OwnerAssignmentCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function hasAssignments($ownerId, $entityClassName, $ownerFieldName, EntityManager $em)
    {
        $findResult = $this->getHasAssignmentsQueryBuilder($ownerId, $entityClassName, $ownerFieldName, $em)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    /**
     * Returns a query builder is used to check if assignments exist
     *
     * @param mixed         $ownerId
     * @param string        $entityClassName
     * @param string        $ownerFieldName
     * @param EntityManager $em
     * @return QueryBuilder
     */
    protected function getHasAssignmentsQueryBuilder($ownerId, $entityClassName, $ownerFieldName, EntityManager $em)
    {
        return $em->getRepository($entityClassName)
            ->createQueryBuilder('entity')
            ->select('owner.id')
            ->innerJoin(sprintf('entity.%s', $ownerFieldName), 'owner')
            ->where('owner.id = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->setMaxResults(1);
    }
}
