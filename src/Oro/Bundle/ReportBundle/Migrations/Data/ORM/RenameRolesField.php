<?php

namespace Oro\Bundle\ReportBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Migration\AbstractRenameField;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Renames roles field to userRoles in reports for User entity.
 */
class RenameRolesField extends AbstractRenameField
{
    /**
     * {@inheritdoc}
     */
    protected function getOldFieldName(): string
    {
        return 'roles';
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewFieldName(): string
    {
        return 'userRoles';
    }

    /**
     * {@inheritdoc}
     */
    protected function getQueryAwareEntities(ObjectManager $manager): array
    {
        return $manager->getRepository(Report::class)->findBy(['entity' => User::class]);
    }
}
