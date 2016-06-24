<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Model\PermissionCategory;
use Oro\Bundle\UserBundle\Model\PermissionCategoryProviderInterface;

class PermissionCategoryProvider implements PermissionCategoryProviderInterface
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
    public function getRolePermissionCategory()
    {
        $categoryList = [];
        $categoryList[] =
            new PermissionCategory(
                PermissionCategoryProviderInterface::DEFAULT_ACTION_CATEGORY,
                'oro.user.permission.category.account_management.label',
                true,
                0
            );
        $categoryList[] = new PermissionCategory('address', 'oro.user.permission.category.address.label', false, 10);
        $categoryList[] =
            new PermissionCategory('application', 'oro.user.permission.category.application.label', false, 20);
        $categoryList[] = new PermissionCategory('calendar', 'oro.user.permission.category.calendar.label', false, 30);
        $categoryList[] = new PermissionCategory('entity', 'oro.user.permission.category.entity.label', false, 40);

        return $categoryList;
    }
}
