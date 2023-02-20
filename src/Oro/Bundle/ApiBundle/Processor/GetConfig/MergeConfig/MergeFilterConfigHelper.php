<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a method to merge entity configuration with configuration of filters.
 */
class MergeFilterConfigHelper
{
    public function mergeFiltersConfig(array $config, array $filtersConfig): array
    {
        if (ConfigUtil::isExcludeAll($filtersConfig) || !\array_key_exists(ConfigUtil::FILTERS, $config)) {
            $config[ConfigUtil::FILTERS] = $filtersConfig;
        } elseif (!empty($filtersConfig[ConfigUtil::FIELDS])) {
            if (!\array_key_exists(ConfigUtil::FIELDS, $config[ConfigUtil::FILTERS])) {
                $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS] = $filtersConfig[ConfigUtil::FIELDS];
            } else {
                $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS] = $this->merge(
                    $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS],
                    $filtersConfig[ConfigUtil::FIELDS]
                );
            }
        }

        return $config;
    }

    private function merge(array $config, array $filtersConfig): array
    {
        foreach ($filtersConfig as $filterName => $filterConfig) {
            foreach ($filterConfig as $key => $val) {
                if (ConfigUtil::FILTER_OPTIONS === $key && isset($config[$filterName][$key])) {
                    $options = $config[$filterName][$key];
                    foreach ($val as $k => $v) {
                        if (isset($options[$k]) && \is_array($options[$k]) && \is_array($v)) {
                            $v = array_merge($options[$k], $v);
                        }
                        $options[$k] = $v;
                    }
                    $val = $options;
                }
                $config[$filterName][$key] = $val;
            }
        }

        return $config;
    }
}
