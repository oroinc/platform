<?php

declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * Updates privileges level for Language entity for all backoffice roles.
 */
class ChangeLanguagePermissions extends AbstractUpdatePermissions
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $roles = $manager->getRepository(Role::class)->findAll();
        $oidDescriptor = ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, Language::class);
        $oid = $aclManager->getOid($oidDescriptor);

        /** @var Role $role */
        foreach ($roles as $role) {
            $sid = $aclManager->getSid($role);
            $privilegePermissions = $this->getPrivilegePermissions(
                $this->getPrivileges($sid),
                $oidDescriptor
            );

            $maskBuilders = $aclManager->getAllMaskBuilders($oid);

            foreach ($maskBuilders as $maskBuilder) {
                $isPermissionChanged = false;
                foreach ($privilegePermissions as $privilegePermission) {
                    if (!in_array($privilegePermission->getAccessLevel(), [
                        AccessLevel::NONE_LEVEL,
                        AccessLevel::GLOBAL_LEVEL,
                        AccessLevel::SYSTEM_LEVEL,
                        AccessLevel::UNKNOWN,
                    ])) {
                        // Fix permission level to Organization
                        $permission = $privilegePermission->getName() . '_GLOBAL';
                        if ($maskBuilder->hasMaskForPermission($permission)) {
                            $maskBuilder->add($permission);
                            $isPermissionChanged = true;
                        }
                    }
                }
                if ($isPermissionChanged) {
                    $aclManager->setPermission($sid, $oid, $maskBuilder->get());
                }
            }
        }
        $aclManager->flush();
    }
}
