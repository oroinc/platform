<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The base class for role privilege providers.
 */
abstract class RolePrivilegeAbstractProvider
{
    private const DEFAULT_ACTION_CATEGORY = 'account_management';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RolePrivilegeCategoryProvider */
    protected $categoryProvider;

    /** @var AclRoleHandler */
    protected $aclRoleHandler;

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
            return $category->getId();
        }, $categories);
        $category = $privilege->getCategory();
        if (\in_array($category, $categories, true)) {
            return $category;
        }

        return self::DEFAULT_ACTION_CATEGORY;
    }

    /**
     * @param AbstractRole $role
     * @param string       $type
     *
     * @return AclPrivilege[]
     */
    protected function preparePrivileges(AbstractRole $role, $type)
    {
        $allPrivileges = [];
        /** @var ArrayCollection $sortedPrivileges */
        foreach ($this->aclRoleHandler->getAllPrivileges($role) as $privilegeType => $sortedPrivileges) {
            if ($privilegeType === $type) {
                $allPrivileges = array_merge($allPrivileges, $sortedPrivileges->toArray());
            }
        }

        return $allPrivileges;
    }
}
