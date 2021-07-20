<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Configuration\PrivilegeCategoryConfigurationProvider;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides information about ACL categories for user role.
 */
class RolePrivilegeCategoryProvider
{
    /** @var PrivilegeCategoryConfigurationProvider */
    private $configurationProvider;

    /** @var TranslatorInterface */
    private $translator;

    /** @var PrivilegeCategory[]|null */
    private $categories;

    public function __construct(
        PrivilegeCategoryConfigurationProvider $configurationProvider,
        TranslatorInterface $translator
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->translator = $translator;
    }

    /**
     * Gets all categories.
     *
     * @return PrivilegeCategory[] Categories sorted by priority
     */
    public function getCategories(): array
    {
        if (null === $this->categories) {
            $categories = [];
            $items = $this->configurationProvider->getCategories();
            foreach ($items as $id => $item) {
                $categories[$item['priority']][] = new PrivilegeCategory(
                    $id,
                    $item['label'],
                    $item['tab'],
                    $item['priority']
                );
            }
            if ($categories) {
                ksort($categories);
                $categories = array_merge(...array_values($categories));
            }
            $this->categories = $categories;
        }

        return $this->categories;
    }

    /**
     * Gets identifiers of all categories marked as tabs.
     *
     * @return string[] Tab identifiers sorted by priority
     */
    public function getTabIds(): array
    {
        $result = [];
        $categories = $this->getCategories();
        foreach ($categories as $category) {
            if ($category->isTab()) {
                $result[] = $category->getId();
            }
        }

        return $result;
    }

    /**
     * Gets tab details for all categories marked as tabs.
     *
     * @return array [['id' => identifier, 'label' => translated name], ...] Details of tabs sorted by priority
     */
    public function getTabs(): array
    {
        $result = [];
        $categories = $this->getCategories();
        foreach ($categories as $category) {
            if ($category->isTab()) {
                $result[] = [
                    'id'    => $category->getId(),
                    'label' => $this->translator->trans($category->getLabel())
                ];
            }
        }

        return $result;
    }
}
