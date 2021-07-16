<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\LoadAclRoles;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

/**
 * Updates permissions for EmailUser entity for ROLE_USER and ROLE_MANAGER roles.
 */
class UpdateEmailAccessLevels extends AbstractUpdatePermissions implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadAclRoles::class];
    }

    public function load(ObjectManager $manager)
    {
        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $roleNames = [LoadRolesData::ROLE_USER, LoadRolesData::ROLE_MANAGER];
        $permissions = ['VIEW_BASIC', 'CREATE_BASIC', 'EDIT_BASIC'];
        foreach ($roleNames as $roleName) {
            $this->replaceEntityPermissions(
                $aclManager,
                $this->getRole($manager, $roleName),
                EmailUser::class,
                $permissions
            );
        }
        $aclManager->flush();
    }
}
