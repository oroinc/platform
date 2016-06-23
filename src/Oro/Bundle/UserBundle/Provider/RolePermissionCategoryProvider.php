<?php

namespace Oro\Bundle\UserBundle\Provider;

class RolePermissionCategoryProvider
{
    const DEFAULT_ACTION_CATEGORY = 'account_management';
    const DEFAULT_ENTITY_CATEGORY = null;

    /**
     * Get all categories
     * 
     * @return array
     */
    public function getList()
    {
        return [
            'account_management' => [
                'id' => 'account_management',
                'label' => 'Account Management',
                'tab' => true
            ],
            'marketing' => [
                'id' => 'marketing',
                'label' => 'Marketing',
                'tab' => true
            ],
            'sales_data' => [
                'id' => 'sales_data',
                'label' => 'Sales Data',
                'tab' => true
            ],
            'address' => [
                'id' => 'address',
                'label' => 'Address',
                'tab' => false
            ],
            'application' => [
                'id' => 'application',
                'label' => 'Applications',
                'tab' => false
            ],
            'calendar' => [
                'id' => 'calendar',
                'label' => 'Calendar',
                'tab' => false
            ],
            'entity' => [
                'id' => 'entity',
                'label' => 'Entities',
                'tab' => false
            ]
        ];
    }

    /**
     * Get categories market as tabbed
     *
     * @return array
     */
    public function getTabbedCategories()
    {
        $categories = array_values($this->getList());
        $tabs = $this->getTabList();
        
        return array_filter($categories, function ($category) use ($tabs) {
            return in_array($category['id'], $tabs, true);
        });
    }

    /**
     * Get list of tabs
     *
     * @return array
     */
    public function getTabList()
    {
        return array_filter(array_map(function ($category) {
            return $category['tab'] ? $category['id'] : null;
        }, $this->getList()));
    }
}
