<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Model\PrivilegeCategoryProviderInterface;

class PrivilegeCategoryProvider implements PrivilegeCategoryProviderInterface
{
    const NAME = 'platform';
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getRolePrivilegeCategory()
    {
        $categoryList = [];
        $categoryList[] =
            new PrivilegeCategory(
                PrivilegeCategoryProviderInterface::DEFAULT_ACTION_CATEGORY,
                'oro.user.privilege.category.account_management.label',
                true,
                0
            );
        $categoryList[] = new PrivilegeCategory('address', 'oro.user.privilege.category.address.label', false, 10);
        $categoryList[] =
            new PrivilegeCategory('application', 'oro.user.privilege.category.application.label', false, 20);
        $categoryList[] = new PrivilegeCategory('calendar', 'oro.user.privilege.category.calendar.label', false, 30);
        $categoryList[] = new PrivilegeCategory('entity', 'oro.user.privilege.category.entity.label', false, 40);

        return $categoryList;
    }
}
