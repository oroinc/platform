<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Model\PrivilegeCategoryProviderInterface;

class RolePrivilegeCategoryProvider
{
    /**
     * @var PrivilegeCategoryProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var PrivilegeCategory[]
     */
    protected $categoryList = [];

    
    /**
     * Add provider to registry
     *
     * @param PrivilegeCategoryProviderInterface $provider
     */
    public function addProvider(PrivilegeCategoryProviderInterface $provider)
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Get all providers
     *
     * @return PrivilegeCategoryProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Get provider by name
     *
     * @param string $name
     *
     * @return null|PrivilegeCategoryProviderInterface
     */
    public function getProviderByName($name)
    {
        if ($this->hasProvider($name)) {
            return $this->providers[$name];
        }

        return null;
    }

    /**
     * Check available provider by name
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasProvider($name)
    {
        return array_key_exists($name, $this->providers);
    }
    
    /**
     * Get all categories
     * 
     * @return PrivilegeCategory[]
     */
    public function getPermissionCategories()
    {
        if ($this->categoryList) {
            return $this->categoryList;
        }

        $categoryList = [];
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            $categories = $provider->getRolePrivilegeCategory();
            if (is_object($categories)) {
                $categories = [$categories];
            }
            $categoryList = array_merge(array_values($categoryList), array_values($categories));
        }

        $orderedCategoryList = [];
        /** @var PrivilegeCategory $category */
        foreach ($categoryList as $category) {
            $priority = $category->getPriority();
            $orderedCategoryList[$priority][] = $category;
        }
        ksort($orderedCategoryList);
        $this->categoryList = call_user_func_array('array_merge', $orderedCategoryList);
        
        return $this->categoryList;
    }
    
    protected function getPredefinedCategories()
    {
        $categoryList = [];
        $categoryList[] = new PrivilegeCategory('sales_data', 'oro.user.role.category.sales_data.label', true, 7);
        
        return $categoryList;
    }

    /**
     * Get categories market as tabbed
     *
     * @return PrivilegeCategory[]
     */
    public function getTabbedCategories()
    {
        $tabs = $this->getTabList();

        return array_filter($this->getPermissionCategories(), function ($category) use ($tabs) {
            /** @var PrivilegeCategory $category */
            return in_array($category->getId(), $tabs, true);
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
            /** @var PrivilegeCategory $category */
            return $category->getTab() ? $category->getId() : null;
        }, $this->getPermissionCategories()));
    }
}
