<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a method to merge entity configuration with configuration of filters.
 */
class MergeFilterConfigHelper
{
    /**
     * @param array $config
     * @param array $filtersConfig
     *
     * @return array
     */
    public function mergeFiltersConfig(array $config, array $filtersConfig)
    {
        if (ConfigUtil::isExcludeAll($filtersConfig) || !\array_key_exists(ConfigUtil::FILTERS, $config)) {
            $config[ConfigUtil::FILTERS] = $filtersConfig;
        } elseif (!empty($filtersConfig[ConfigUtil::FIELDS])) {
            if (!\array_key_exists(ConfigUtil::FIELDS, $config[ConfigUtil::FILTERS])) {
                $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS] = $filtersConfig[ConfigUtil::FIELDS];
            } else {
                $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS] = \array_merge(
                    $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS],
                    $filtersConfig[ConfigUtil::FIELDS]
                );
            }
        }

        return $config;
    }
}
