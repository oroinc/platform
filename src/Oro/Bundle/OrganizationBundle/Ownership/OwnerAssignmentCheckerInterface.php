<?php

namespace Oro\Bundle\OrganizationBundle\Ownership;

use Doctrine\ORM\EntityManager;

interface OwnerAssignmentCheckerInterface
{
    /**
     * Checks if the given owner owns at least one entity of the given type
     *
     * @param mixed         $ownerId
     * @param string        $entityClassName
     * @param string        $ownerFieldName
     * @param EntityManager $em
     * @return bool
     */
    public function hasAssignments($ownerId, $entityClassName, $ownerFieldName, EntityManager $em);
}
