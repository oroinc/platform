<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

class RolePrivilegeCapabilityProvider extends RolePrivilegeAbstractProvider
{
    /**
     * @param AbstractRole $role
     *
     * @return mixed
     */
    public function getCapabilities(AbstractRole $role)
    {
        $categories = $this->categoryProvider->getPermissionCategories();
        $capabilitiesData = $this->getCapabilitiesData($categories);
        $allPrivileges = $this->preparePrivileges($role, 'action');

        /** @var AclPrivilege $privilege */
        foreach ($allPrivileges as $privilege) {
            $category = $this->getPrivilegeCategory($privilege, $categories);
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
                'selected_access_level'   => AccessLevel::SYSTEM_LEVEL,
                'unselected_access_level' => AccessLevel::NONE_LEVEL            ];
        }
        $capabilitiesData = $this->filterUnusedCategories($capabilitiesData);

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

    /**
     * @param $capabilitiesData
     *
     * @return array
     */
    protected function filterUnusedCategories($capabilitiesData)
    {
        $capabilitiesData = array_filter($capabilitiesData, function ($data) {
            return count($data['items']) > 0;
        });

        return $capabilitiesData;
    }
}
