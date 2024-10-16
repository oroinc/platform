<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Migration\AbstractRenameField;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Renames roles field to userRoles in segments for User entity.
 */
class RenameRolesField extends AbstractRenameField
{
    #[\Override]
    protected function getOldFieldName(): string
    {
        return 'roles';
    }

    #[\Override]
    protected function getNewFieldName(): string
    {
        return 'userRoles';
    }

    #[\Override]
    protected function getQueryAwareEntities(ObjectManager $manager): array
    {
        return $manager->getRepository(Segment::class)->findBy(['entity' => User::class]);
    }
}
