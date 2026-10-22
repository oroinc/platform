<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * On upgrade, limits the "VIEW" permission on import/export results to the "User" access level for every role,
 * except the administrator one, that has a wider level.
 */
class UpdateImportExportResultViewPermission extends AbstractUpdatePermissions
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $oidDescriptor = ObjectIdentityHelper::encodeIdentityString(
            EntityAclExtension::NAME,
            ImportExportResult::class
        );

        /** @var Role[] $roles */
        $roles = $manager->getRepository(Role::class)->findAll();
        foreach ($roles as $role) {
            if (User::ROLE_ADMINISTRATOR === $role->getRole()) {
                continue;
            }

            $privileges = $this->getPrivileges($aclManager->getSid($role));
            $viewPermission = $this->getPrivilegePermissions($privileges, $oidDescriptor)->get('VIEW');
            if (null !== $viewPermission && $viewPermission->getAccessLevel() > AccessLevel::BASIC_LEVEL) {
                $this->setEntityPermissions($aclManager, $role, ImportExportResult::class, ['VIEW_BASIC']);
            }
        }

        $aclManager->flush();
    }
}
