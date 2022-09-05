<?php

namespace Oro\Bundle\UserBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for configuration of ACL categories that is loaded
 * from "Resources/config/oro/acl_categories.yml" files.
 */
class PrivilegeCategoryConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/acl_categories.yml';

    /**
     * @return array [category id => ['label' => string, 'tab' => bool, 'priority' => int], ...]
     */
    public function getCategories(): array
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_acl_categories', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (isset($resource->data[PrivilegeCategoryConfiguration::ROOT_NODE])) {
                $configs[] = $resource->data[PrivilegeCategoryConfiguration::ROOT_NODE];
            }
        }

        $allItems = CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new PrivilegeCategoryConfiguration(),
            $configs
        );

        $visibleItems = [];
        foreach ($allItems as $id => $item) {
            if ($item['visible']) {
                unset($item['visible']);
                $visibleItems[$item['priority']][$id] = $item;
            }
        }
        if ($visibleItems) {
            ksort($visibleItems);
            $visibleItems = array_merge(...array_values($visibleItems));
        }

        return $visibleItems;
    }
}
