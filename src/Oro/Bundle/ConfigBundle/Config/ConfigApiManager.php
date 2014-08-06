<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Provider\ProviderInterface;

class ConfigApiManager
{
    /** @var ProviderInterface */
    protected $configProvider;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ProviderInterface $configProvider
     * @param ConfigManager     $configManager
     */
    public function __construct(ProviderInterface $configProvider, ConfigManager $configManager)
    {
        $this->configProvider = $configProvider;
        $this->configManager  = $configManager;
    }

    /**
     * Gets the list of all configuration sections' paths
     * The result is sorted alphabetically
     *
     * @return string[]
     */
    public function getSections()
    {
        $sections = [];

        $tree = $this->configProvider->getApiTree();
        $this->extractSectionPaths($sections, $tree, null);
        sort($sections, SORT_FLAG_CASE);

        return $sections;
    }

    /**
     * Gets all configuration data of the specified section
     *
     * @param string $path The path to API section. For example: lookAndFeel/grid
     *
     * @return array
     */
    public function getData($path)
    {
        $data = $this->configProvider->getApiTree($path);
        $this->prepareData($data);

        return $data;
    }

    /**
     * Extracts paths of all sections in the given configuration tree
     *
     * @param array  $result
     * @param array  $tree
     * @param string $parentPath
     */
    protected function extractSectionPaths(array &$result, array $tree, $parentPath)
    {
        foreach ($tree as $key => $val) {
            if (is_array($val)) {
                $path = empty($parentPath) ? $key : $parentPath . '/' . $key;
                $result[] = $path;
                $this->extractSectionPaths($result, $val, $path);
            }
        }
    }

    /**
     * Replaces variable references by values
     *
     * @param array $data
     */
    protected function prepareData(array &$data)
    {
        foreach ($data as &$val) {
            if (is_array($val)) {
                $this->prepareData($val);
            } else {
                $val = $this->configManager->get($val);
            }
        }
    }
}
