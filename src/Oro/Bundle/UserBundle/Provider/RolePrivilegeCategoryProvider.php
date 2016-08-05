<?php

namespace Oro\Bundle\UserBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;

class RolePrivilegeCategoryProvider
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @var PrivilegeCategoryProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var PrivilegeCategory[]
     */
    protected $categoryList = [];

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
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
     * Get category by name
     *
     * @param string $categoryName
     *
     * @return PrivilegeCategory|null
     */
    public function getCategory($categoryName)
    {
        foreach ($this->getAllCategories() as $category) {
            if ($category->getId() === $categoryName) {
                return $category;
            }
        }
    }

    /**
     * Get all categories
     *
     * @return array
     */
    public function getAllCategories()
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
        $this->categoryList = $categoryList;

        return $this->categoryList;
    }

    /**
     * Get all categories
     *
     * @return PrivilegeCategory[]
     */
    public function getPermissionCategories()
    {
        $categoryList = $this->getAllCategories();

        $orderedCategoryList = [];
        /** @var PrivilegeCategory $category */
        foreach ($categoryList as $category) {
            if ($category->isVisible()) {
                $priority = $category->getPriority();
                $orderedCategoryList[$priority][] = $category;
            }
        }
        ksort($orderedCategoryList);
        $categoryList = call_user_func_array('array_merge', $orderedCategoryList);
        
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
            return $category->isTab() ? $category->getId() : null;
        }, $this->getPermissionCategories()));
    }

    /**
     * Get tabs
     *
     * @return array
     */
    public function getTabs()
    {
        return array_values(
            array_map(function ($tab) {
                /** @var PrivilegeCategory $tab */
                return [
                    'id' => $tab->getId(),
                    'label' => $this->translator->trans($tab->getLabel())
                ];
            }, $this->getTabbedCategories())
        );
    }
}
