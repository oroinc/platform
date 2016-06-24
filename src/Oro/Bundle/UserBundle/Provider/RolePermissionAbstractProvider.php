<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Model\PermissionCategory;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Model\PermissionCategoryProviderInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

abstract class RolePermissionAbstractProvider
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
     * @param AclPrivilege         $privilege
     * @param PermissionCategory[] $categories
     *
     * @return string
     */
    protected function getPrivelegeCategory(AclPrivilege $privilege, $categories)
    {
        $categories = array_map(function ($category) {
            /** @var PermissionCategory $category */
            return $category->getId();
        }, $categories);
        $category = $privilege->getCategory();
        if (!in_array($category, $categories)) {
            $category = PermissionCategoryProviderInterface::DEFAULT_ACTION_CATEGORY;

            return $category;
        }

        return $category;
    }
    
    /**
     * @param Role $role
     * @param string $type
     *
     * @return array
     */
    protected function preparePriveleges(Role $role, $type)
    {
        $allPrivileges = [];
        /**
         * @var string $type
         * @var ArrayCollection $sortedPrivileges
         */
        foreach ($this->aclRoleHandler->getAllPriveleges($role) as $privelegeType => $sortedPrivileges) {
            if ($privelegeType === $type) {
                $allPrivileges = array_merge($allPrivileges, $sortedPrivileges->toArray());
            }
        }

        return $allPrivileges;
    }

}
