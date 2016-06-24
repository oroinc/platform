<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\Role;

class RolePermissionCapabilityProvider extends RolePermissionAbstractProvider
{
    /**
     * @param Role $role
     *
     * @return mixed
     */
    public function getCapabilities(Role $role)
    {
        $categories = $this->categoryProvider->getPermissionCategories();
        $capabilitiesData = $this->getCapabilitiesData($categories);
        $allPrivileges = $this->preparePriveleges($role, 'action');

        /** @var AclPrivilege $privilege */
        foreach ($allPrivileges as $privilege) {
            $category = $this->getPrivelegeCategory($privilege, $categories);
            $permissions = $privilege->getPermissions()->toArray();
            $permission = reset($permissions);
            $description = $privilege->getDescription() ? $this->translator->trans($privilege->getDescription()) : '';
            $capabilitiesData[$category]['items'][] = [
                'id'                      => $privilege->getIdentity()->getId(),
                'identity'                => $privilege->getIdentity()->getId(),
                'label'                   => $this->translator->trans($privilege->getIdentity()->getName()),
                'description'             => $description,
                'name'                    => $permission->getName(),
                'access_level'            => $permission->getAccessLevel(),
                'selected_access_level'   => RolePermissionAbstractProvider::SELECTED_ACCESS_LEVEL,
                'unselected_access_level' => RolePermissionAbstractProvider::UNSELECTED_ACCESS_LEVEL
            ];
        }
        
        return array_values($capabilitiesData);
    }

    /**
     * @param array $categories
     *
     * @return array
     */
    protected function getCapabilitiesData($categories)
    {
        $capabilitiesData = [];
        foreach ($categories as $category) {
            $capabilitiesData[$category->getId()] = [
                'group' => $category->getId(),
                'label' => $this->translator->trans($category->getLabel()),
                'items' => []
            ];
        }

        return $capabilitiesData;
    }
}
