<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig;

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

    /**
     * @param MergeActionConfigHelper $mergeActionConfigHelper
     * @param MergeFilterConfigHelper $mergeFilterConfigHelper
     */
    public function __construct(
        MergeActionConfigHelper $mergeActionConfigHelper,
        MergeFilterConfigHelper $mergeFilterConfigHelper
    ) {
        $this->mergeActionConfigHelper = $mergeActionConfigHelper;
        $this->mergeFilterConfigHelper = $mergeFilterConfigHelper;
    }

    /**
     * @param array  $config
     * @param array  $subresourceConfig
     * @param string $action
     * @param bool   $withStatusCodes
     * @param bool   $withFilters
     *
     * @return array
     */
    public function mergeSubresourcesConfig(
        array $config,
        array $subresourceConfig,
        $action,
        $withStatusCodes,
        $withFilters
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

        return $config;
    }
}
