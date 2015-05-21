<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Provider\ProviderInterface;

class ConfigApiManager
{
    /** @var ProviderInterface */
    protected $configProvider;

    /** @var ConfigManager[] */
    protected $configManagers;

    /**
     * @param ProviderInterface $configProvider
     */
    public function __construct(ProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function addConfigManager($scope, $manager)
    {
        $this->configManagers[$scope] = $manager;
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
    public function getData($path, $scope = 'user')
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->configManagers[$scope];
        $variables = $this->configProvider->getApiTree($path)->getVariables(true);
        $result    = [];
        foreach ($variables as $variable) {
            $value = $configManager->get($variable->getKey());
            $dataTransformer = $this->configProvider->getDataTransformer($variable->getKey());
            if ($dataTransformer !== null) {
                $value = $dataTransformer->transform($value);
            }
            $var          = $variable->toArray();
            $var['value'] = $this->getTypedValue($variable->getType(), $value);
            $var          = array_merge($var, $configManager->getInfo($variable->getKey()));
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

    /**
     * @param string $type
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function getTypedValue($type, $value)
    {
        if ($value !== null) {
            switch ($type) {
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'integer':
                    $value = (int)$value;
                    break;
            }
        }

        return $value;
    }
}
