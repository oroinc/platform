<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a method to merge entity configuration with configuration of sorters.
 */
class MergeSorterConfigHelper
{
    public function mergeSortersConfig(array $config, array $sortersConfig): array
    {
        if (ConfigUtil::isExcludeAll($sortersConfig) || !\array_key_exists(ConfigUtil::SORTERS, $config)) {
            $config[ConfigUtil::SORTERS] = $sortersConfig;
        } elseif (!empty($sortersConfig[ConfigUtil::FIELDS])) {
            if (!\array_key_exists(ConfigUtil::FIELDS, $config[ConfigUtil::SORTERS])) {
                $config[ConfigUtil::SORTERS][ConfigUtil::FIELDS] = $sortersConfig[ConfigUtil::FIELDS];
            } else {
                $config[ConfigUtil::SORTERS][ConfigUtil::FIELDS] = array_merge(
                    $config[ConfigUtil::SORTERS][ConfigUtil::FIELDS],
                    $sortersConfig[ConfigUtil::FIELDS]
                );
            }
        }

        return $config;
    }
}
