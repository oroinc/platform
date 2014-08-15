<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
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
     * Gets the list of paths for all configuration sections
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
     * @param string $path The path to API section. For example: look-and-feel/grid
     *
     * @return array
     */
    public function getData($path)
    {
        $variables = $this->configProvider->getApiTree($path)->getVariables(true);
        $result    = [];
        foreach ($variables as $variable) {
            $var          = $variable->toArray();
            $var['value'] = $this->configManager->get($variable->getKey());
            $result[]     = $var;
        }

        return $result;
    }

    /**
     * Extracts paths of all sections in the given configuration tree
     *
     * @param array             $result
     * @param SectionDefinition $tree
     * @param string            $parentPath
     */
    protected function extractSectionPaths(array &$result, SectionDefinition $tree, $parentPath)
    {
        $subSections = $tree->getSubSections();
        foreach ($subSections as $subSection) {
            $path     = empty($parentPath)
                ? $subSection->getName()
                : $parentPath . '/' . $subSection->getName();
            $result[] = $path;
            $this->extractSectionPaths($result, $subSection, $path);
        }
    }
}
