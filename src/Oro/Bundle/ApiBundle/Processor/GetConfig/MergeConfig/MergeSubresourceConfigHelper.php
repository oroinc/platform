<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a method to merge entity configuration with configuration of subresource section.
 */
class MergeSubresourceConfigHelper
{
    /** @var MergeActionConfigHelper */
    private $mergeActionConfigHelper;

    /** @var MergeFilterConfigHelper */
    private $mergeFilterConfigHelper;

    /** @var MergeSorterConfigHelper */
    private $mergeSorterConfigHelper;

    public function __construct(
        MergeActionConfigHelper $mergeActionConfigHelper,
        MergeFilterConfigHelper $mergeFilterConfigHelper,
        MergeSorterConfigHelper $mergeSorterConfigHelper
    ) {
        $this->mergeActionConfigHelper = $mergeActionConfigHelper;
        $this->mergeFilterConfigHelper = $mergeFilterConfigHelper;
        $this->mergeSorterConfigHelper = $mergeSorterConfigHelper;
    }

    /**
     * @param array  $config
     * @param array  $subresourceConfig
     * @param string $action
     * @param bool   $withStatusCodes
     * @param bool   $withFilters
     * @param bool   $withSorters
     *
     * @return array
     */
    public function mergeSubresourcesConfig(
        array $config,
        array $subresourceConfig,
        $action,
        $withStatusCodes,
        $withFilters,
        $withSorters
    ) {
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
