<?php

namespace Oro\Bundle\OrganizationBundle\Ownership;

use Doctrine\ORM\EntityManagerInterface;

/**
 * An interface for classes that implements an algorithm to check owner assignment.
 */
interface OwnerAssignmentCheckerInterface
{
    /**
     * Checks if the given owner owns at least one entity of the given type.
     *
     * @param mixed                  $ownerId
     * @param string                 $entityClassName
     * @param string                 $ownerFieldName
     * @param EntityManagerInterface $em
     *
     * @return bool
     */
    public function hasAssignments(
        $ownerId,
        string $entityClassName,
        string $ownerFieldName,
        EntityManagerInterface $em
    ): bool;
}
