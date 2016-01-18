<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

class MixinConfigurationHelper
{
    const ROOT_ALIAS_PLACEHOLDER = '__root_entity__';

    /** @var ConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var array */
    protected $pathsToFix = [
        '[columns]',
        '[sorters][columns]',
        '[filters][columns]',
        '[source][query]'
    ];

    /**
     * @param ConfigurationProviderInterface $configurationProvider
     */
    public function __construct(ConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param DatagridConfiguration $configuration
     * @param string                $gridName
     *
     * @return DatagridConfiguration
     */
    public function extendConfiguration(DatagridConfiguration $configuration, $gridName)
    {
        $gridConfiguration = $this->configurationProvider->getConfiguration($gridName);
        $basicAlias        = $configuration->offsetGetByPath('[source][query][from][0][alias]');
        foreach ($this->pathsToFix as $path) {
            $forFix = $gridConfiguration->offsetGetByPath($path);
            if ($forFix) {
                $gridConfiguration->offsetSetByPath(
                    $path,
                    $this->fixMixinAlias($basicAlias, $forFix)
                );
            }
        }

        $scopes = array_diff(array_keys($gridConfiguration->getIterator()->getArrayCopy()), ['name']);
        foreach ($scopes as $scope) {
            $path             = sprintf('[%s]', $scope);
            $additionalParams = $gridConfiguration->offsetGetByPath($path);
            $baseParams       = $configuration->offsetGetByPath($path, []);

            if (!is_array($additionalParams) || !is_array($baseParams)) {
                continue;
            }

            $configuration->offsetSetByPath(
                $path,
                self::arrayMergeRecursiveAppendDistinct($baseParams, $additionalParams)
            );
        }

        return $configuration;
    }

    /**
     * Recursively merge arrays.
     *
     * Merge two arrays as array_merge_recursive do, but instead of converting values to arrays when keys are same
     * keeps value from the first array unchangeable.
     *
     * @param array $first
     * @param array $second
     *
     * @return array
     */
    public static function arrayMergeRecursiveAppendDistinct(array $first, array $second)
    {
        foreach ($second as $idx => $value) {
            if (is_integer($idx)) {
                if (is_array($value)) {
                    $first[] = $value;
                } elseif (!in_array($value, $first, true)) { // Checks if value already exists in array
                    $first[] = $value;
                }
            } else {
                if (!array_key_exists($idx, $first)) {
                    $first[$idx] = $value;
                } else {
                    if (is_array($value)) {
                        $first[$idx] = self::arrayMergeRecursiveAppendDistinct($first[$idx], $value);
                    }
                }
            }
        }

        return $first;
    }

    /**
     * @param string $alias
     * @param mixed  $configuration
     *
     * @return array|mixed
     */
    protected function fixMixinAlias($alias, $configuration)
    {
        if (is_array($configuration)) {
            foreach ($configuration as $key => $value) {
                $configuration[$key] = $this->fixMixinAlias($alias, $configuration[$key]);
            }
        } elseif (is_string($configuration)) {
            $configuration = str_replace(self::ROOT_ALIAS_PLACEHOLDER, $alias, $configuration);
        }

        return $configuration;
    }
}
