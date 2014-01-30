<?php

namespace Oro\Bundle\TagBundle\Ownership;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerAssignmentCheckerInterface;

class TagOwnerAssignmentChecker implements OwnerAssignmentCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function hasAssignments($ownerId, $entityClassName, $ownerFieldName, EntityManager $em)
    {
        // we do not need to check owner assignments for Tag entity because in case when
        // an tag owner is deleted its tags are just return back to system pull (we just set
        // an owner field as NULL)
        // TODO: need additional discussion about this logic
        return false;
    }
}
