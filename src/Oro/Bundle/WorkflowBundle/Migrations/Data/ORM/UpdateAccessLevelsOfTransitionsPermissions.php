<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadUserRolesData;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclExtension;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Finds workflow transitions permissions with invalid access level and updates them to the maximum allowed access
 * level.
 */
class UpdateAccessLevelsOfTransitionsPermissions extends AbstractUpdatePermissions implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [LoadUserRolesData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        /** @var WorkflowDefinition[] $workflowDefinitions */
        $workflowDefinitions = $manager->getRepository(WorkflowDefinition::class)->findAll();

        $fieldAclPrivilegeRepo = $this->getAclPrivilegeRepository();

        foreach ($this->getRoles($manager) as $role) {
            $sid = $aclManager->getSid($role);
            $allRolePrivileges = $this->getPrivileges($sid);
            $updatedPrivileges = [[]];
            foreach ($workflowDefinitions as $workflowDefinition) {
                $updatedPrivileges[] = $this->updatePrivilegesForWorkflow(
                    $aclManager,
                    $allRolePrivileges,
                    $workflowDefinition->getName()
                );
            }
            $updatedPrivileges = array_merge(...$updatedPrivileges);
            if ($updatedPrivileges) {
                $fieldAclPrivilegeRepo->savePrivileges($sid, new ArrayCollection($updatedPrivileges));
            }
        }
    }

    /**
     * Roles for which to check if the access levels of the transitions permissions are valid and update if not.
     *
     * @param ObjectManager $manager
     * @return AbstractRole[]
     */
    protected function getRoles(ObjectManager $manager): array
    {
        return $manager->getRepository(Role::class)->findAll();
    }

    /**
     * Checks if the access levels of the transitions permissions of the specified $allRolePrivileges are valid
     * and if not then changes them to the max allowed access level.
     *
     * @param AclManager $aclManager
     * @param Collection<AclPrivilege> $allRolePrivileges
     * @param string $workflowName
     * @return AclPrivilege[] Array of AclPrivilege models which were changed and must be updated in database.
     */
    protected function updatePrivilegesForWorkflow(
        AclManager $aclManager,
        Collection $allRolePrivileges,
        string $workflowName
    ): array {
        $updatedPrivileges = [];

        /** @var AclExtensionInterface $extension */
        $extension = $aclManager->getExtensionSelector()->selectByExtensionKey(WorkflowAclExtension::NAME);

        /** @var AclExtensionInterface $fieldExtension */
        $fieldExtension = $extension->getFieldExtension();

        $oidDescriptor = ObjectIdentityHelper::encodeIdentityString(WorkflowAclExtension::NAME, $workflowName);
        $oid = $aclManager->getOid($oidDescriptor);

        foreach ($allRolePrivileges as $aclPrivilege) {
            if ($aclPrivilege->getIdentity()->getId() !== $oidDescriptor) {
                // Skips the privileges not related the specified $workflow.
                continue;
            }

            $isUpdated = false;
            foreach ($aclPrivilege->getFields() as $fieldAclPrivilege) {
                $fieldAclPrivilegeIdentity = $fieldAclPrivilege->getIdentity();
                [, $fieldName] = ObjectIdentityHelper::decodeEntityFieldInfo($fieldAclPrivilegeIdentity->getId());
                $allowedPermissions = $fieldExtension->getAllowedPermissions($oid, $fieldName);

                foreach ($allowedPermissions as $permission) {
                    if (!$fieldAclPrivilege->hasPermission($permission)) {
                        continue;
                    }

                    $allowedAccessLevels = $fieldExtension->getAccessLevelNames(
                        $fieldAclPrivilegeIdentity->getId(),
                        $permission
                    );
                    /** @var AclPermission $fieldAclPermission */
                    $fieldAclPermission = $fieldAclPrivilege->getPermissions()->get($permission);
                    // If the access level set for permission is not in the list of allowed access levels
                    if (!isset($allowedAccessLevels[$fieldAclPermission->getAccessLevel()])) {
                        // Then removes invalid permission
                        $fieldAclPrivilege->removePermission($fieldAclPermission);

                        // And sets a new one with the correct maximum access level
                        $maxAccessLevel = max(array_keys($allowedAccessLevels));
                        $fieldAclPrivilege->addPermission(new AclPermission($permission, $maxAccessLevel));

                        $isUpdated = true;
                    }
                }
            }

            if ($isUpdated) {
                $updatedPrivileges[] = $aclPrivilege;
            }
        }

        return $updatedPrivileges;
    }
}
