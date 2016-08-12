<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;

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
        $categoryList[] = new PrivilegeCategory(
            PrivilegeCategoryProviderInterface::DEFAULT_ACTION_CATEGORY,
            'oro.user.privilege.category.account_management.label',
            true,
            0
        );
        $categoryList[] = new PrivilegeCategory(
            'shopping',
            'orob2b.account.accountuserrole.privilege.category.shopping.label',
            true,
            10
        );
        $categoryList[] = new PrivilegeCategory(
            'quotes',
            'orob2b.account.accountuserrole.privilege.category.quotes.label',
            true,
            20
        );
        $categoryList[] = new PrivilegeCategory(
            'checkout',
            'orob2b.account.accountuserrole.privilege.category.checkout.label',
            true,
            30
        );
        $categoryList[] = new PrivilegeCategory(
            'orders',
            'orob2b.account.accountuserrole.privilege.category.orders.label',
            true,
            40
        );

        $categoryList[] = new PrivilegeCategory(
            'address',
            'oro.user.privilege.category.address.label',
            false,
            60
        );
        $categoryList[] = new PrivilegeCategory(
            'application',
            'oro.user.privilege.category.application.label',
            false,
            70
        );
        $categoryList[] = new PrivilegeCategory(
            'calendar',
            'oro.user.privilege.category.calendar.label',
            false,
            80
        );
        $categoryList[] = new PrivilegeCategory(
            'entity',
            'oro.user.privilege.category.entity.label',
            false,
            90
        );

        return $categoryList;
    }
}
