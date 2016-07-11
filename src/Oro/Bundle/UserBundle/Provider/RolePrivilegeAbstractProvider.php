<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

abstract class RolePrivilegeAbstractProvider
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var RolePrivilegeCategoryProvider */
    protected $categoryProvider;

    /** @var AclRoleHandler */
    protected $aclRoleHandler;

    /**
     * @param TranslatorInterface           $translator
     * @param RolePrivilegeCategoryProvider $categoryProvider
     * @param AclRoleHandler                $aclRoleHandler
     */
    public function __construct(
        TranslatorInterface $translator,
        RolePrivilegeCategoryProvider $categoryProvider,
        AclRoleHandler $aclRoleHandler
    ) {
        $this->translator = $translator;
        $this->categoryProvider = $categoryProvider;
        $this->aclRoleHandler = $aclRoleHandler;
    }

    /**
     * @param AclPrivilege        $privilege
     * @param PrivilegeCategory[] $categories
     *
     * @return string
     */
    protected function getPrivilegeCategory(AclPrivilege $privilege, $categories)
    {
        $categories = array_map(function ($category) {
            /** @var PrivilegeCategory $category */
            return $category->getId();
        }, $categories);
        $category = $privilege->getCategory();
        if (!in_array($category, $categories)) {
            $category = PrivilegeCategoryProviderInterface::DEFAULT_ACTION_CATEGORY;

            return $category;
        }

        return $category;
    }
    
    /**
     * @param AbstractRole $role
     * @param string $type
     *
     * @return array
     */
    protected function preparePrivileges(AbstractRole $role, $type)
    {
        $allPrivileges = [];
        /**
         * @var string $type
         * @var ArrayCollection $sortedPrivileges
         */
        foreach ($this->aclRoleHandler->getAllPrivileges($role) as $privilegeType => $sortedPrivileges) {
            if ($privilegeType === $type) {
                $allPrivileges = array_merge($allPrivileges, $sortedPrivileges->toArray());
            }
        }

        return $allPrivileges;
    }
}
