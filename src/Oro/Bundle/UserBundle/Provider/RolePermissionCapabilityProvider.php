<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

class RolePermissionCapabilityProvider
{
    const UNSELECTED_ACCESS_LEVEL = 0;
    const SELECTED_ACCESS_LEVEL = 5;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RolePermissionCategoryProvider */
    protected $categoryProvider;

    /** @var AclRoleHandler */
    protected $aclRoleHandler;

    /**
     * @param TranslatorInterface            $translator
     * @param RolePermissionCategoryProvider $categoryProvider
     * @param AclRoleHandler                 $aclRoleHandler
     */
    public function __construct(
        TranslatorInterface $translator,
        RolePermissionCategoryProvider $categoryProvider,
        AclRoleHandler $aclRoleHandler
    ) {
        $this->translator = $translator;
        $this->categoryProvider = $categoryProvider;
        $this->aclRoleHandler = $aclRoleHandler;
    }

    /**
     * @param Role $role
     *
     * @return mixed
     */
    public function getCapabilities(Role $role)
    {
        $categories = array_values($this->categoryProvider->getList());
        $capabilitiesData = $this->getCapabilitiesData($categories);
        $allPrivileges = $this->preparePriveleges($role);

        foreach ($allPrivileges as $privilege) {
            $category = $this->getPrivelegeCategory($privilege, $capabilitiesData);
            $permissions = $privilege->getPermissions()->toArray();
            $permission = reset($permissions);
            $capabilitiesData[$category]['items'][] = [
                'id'                      => $privilege->getIdentity()->getId(),
                'identity'                => $privilege->getIdentity()->getId(),
                'label'                   => $this->translator->trans(
                    $privilege->getIdentity()->getName()
                ),
                'description'             => $privilege->getDescription()
                    ? $this->translator->trans(
                        $privilege->getDescription()
                    ) : '',
                'name'                    => $permission->getName(),
                'access_level'            => $permission->getAccessLevel(),
                'selected_access_level'   => self::SELECTED_ACCESS_LEVEL,
                'unselected_access_level' => self::UNSELECTED_ACCESS_LEVEL
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
            $capabilitiesData[$category['id']] = [
                'group' => $category['id'],
                'label' => $category['label'],
                'items' => []
            ];
        }

        return $capabilitiesData;
    }

    /**
     * @param Role $role
     *
     * @return array
     */
    protected function preparePriveleges(Role $role)
    {
        $allPrivileges = [];
        /**
         * @var string $type
         * @var ArrayCollection $sortedPrivileges
         */
        foreach ($this->aclRoleHandler->getAllPriveleges($role) as $type => $sortedPrivileges) {
            if ($type === 'action') {
                $allPrivileges = array_merge($allPrivileges, $sortedPrivileges->toArray());
            }
        }

        return $allPrivileges;
    }

    /**
     * @param AclPrivilege $privilege
     * @param array $capabilitiesData
     *
     * @return string
     */
    protected function getPrivelegeCategory(AclPrivilege $privilege, $capabilitiesData)
    {
        $category = $privilege->getCategory();
        if (!array_key_exists($category, $capabilitiesData)) {
            $category = RolePermissionCategoryProvider::DEFAULT_ACTION_CATEGORY;

            return $category;
        }

        return $category;
    }
}
