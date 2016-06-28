<?php

namespace Oro\Bundle\UserBundle\Provider;

interface PrivilegeCategoryProviderInterface
{
    const DEFAULT_ACTION_CATEGORY = 'account_management';
    const DEFAULT_ENTITY_CATEGORY = null;

    /**
     * Get provider name
     *
     * @return string
     */
    public function getName();

    /**
     * Get entity role permission category
     *
     * @return PrivilegeCategory|PrivilegeCategory[]
     */
    public function getRolePrivilegeCategory();
}
