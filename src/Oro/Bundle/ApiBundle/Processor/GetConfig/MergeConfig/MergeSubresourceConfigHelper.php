<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a method to merge entity configuration with configuration of subresource section.
 */
class MergeSubresourceConfigHelper
{
    private MergeActionConfigHelper $mergeActionConfigHelper;
    private MergeFilterConfigHelper $mergeFilterConfigHelper;
    private MergeSorterConfigHelper $mergeSorterConfigHelper;

    public function __construct(
        MergeActionConfigHelper $mergeActionConfigHelper,
        MergeFilterConfigHelper $mergeFilterConfigHelper,
        MergeSorterConfigHelper $mergeSorterConfigHelper
    ) {
        $this->mergeActionConfigHelper = $mergeActionConfigHelper;
        $this->mergeFilterConfigHelper = $mergeFilterConfigHelper;
        $this->mergeSorterConfigHelper = $mergeSorterConfigHelper;
    }

    public function mergeSubresourcesConfig(
        array $config,
        array $subresourceConfig,
        string $action,
        bool $withStatusCodes,
        bool $withFilters,
        bool $withSorters
    ): array {
        if (!empty($subresourceConfig[ConfigUtil::ACTIONS][$action])) {
            $config = $this->mergeActionConfigHelper->mergeActionConfig(
                $config,
                $subresourceConfig[ConfigUtil::ACTIONS][$action],
                $withStatusCodes
            );
        }
        if ($withFilters && !empty($subresourceConfig[ConfigUtil::FILTERS])) {
            $config = $this->mergeFilterConfigHelper->mergeFiltersConfig(
                $config,
                $subresourceConfig[ConfigUtil::FILTERS]
            );
        }
        if ($withSorters && !empty($subresourceConfig[ConfigUtil::SORTERS])) {
            $config = $this->mergeSorterConfigHelper->mergeSortersConfig(
                $config,
                $subresourceConfig[ConfigUtil::SORTERS]
            );
        }

        return $config;
    }
}
