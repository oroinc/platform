<?php

namespace Oro\Bundle\TagBundle\Ownership;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerAssignmentCheckerInterface;

/**
 * The owner assignment checker for Tag entity.
 */
class TagOwnerAssignmentChecker implements OwnerAssignmentCheckerInterface
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
        // we do not need to check owner assignments for Tag entity
        // because when an tag owner is deleted its tags are just return back to system pull
        // (we just set an owner field to NULL)
        return false;
    }
}
