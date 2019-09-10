<?php

namespace Oro\Bundle\OrganizationBundle\Ownership;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * The default implementation of the owner assignment checker.
 */
class OwnerAssignmentChecker implements OwnerAssignmentCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function hasAssignments(
        $ownerId,
        string $entityClassName,
        string $ownerFieldName,
        EntityManagerInterface $em
    ): bool {
        $findResult = $this->getHasAssignmentsQueryBuilder($ownerId, $entityClassName, $ownerFieldName, $em)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    /**
     * Gets a query builder is used to check if assignments exist.
     *
     * @param mixed                  $ownerId
     * @param string                 $entityClassName
     * @param string                 $ownerFieldName
     * @param EntityManagerInterface $em
     *
     * @return QueryBuilder
     */
    protected function getHasAssignmentsQueryBuilder(
        $ownerId,
        string $entityClassName,
        string $ownerFieldName,
        EntityManagerInterface $em
    ): QueryBuilder {
        return $em->createQueryBuilder()
            ->from($entityClassName, 'entity')
            ->select('owner.id')
            ->innerJoin('entity.' . $ownerFieldName, 'owner')
            ->where('owner.id = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->setMaxResults(1);
    }
}
