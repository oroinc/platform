<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
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

    /**
     * @param string        $scope
     * @param ConfigManager $manager
     */
    public function addConfigManager($scope, ConfigManager $manager)
    {
        $this->configManagers[$scope] = $manager;
    }

    /**
     * @param string $scope
     *
     * @return ConfigManager|null
     */
    public function getConfigManager($scope)
    {
        return isset($this->configManagers[$scope])
            ? $this->configManagers[$scope]
            : null;
    }

    /**
     * Gets all configuration scopes
     *
     * @return string[]
     */
    public function getScopes()
    {
        return array_keys($this->configManagers);
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
     * Checks whether a section with a given path exists
     *
     * @param string $path The path to API section. For example: look-and-feel/grid
     *
     * @return bool
     */
    public function hasSection($path)
    {
        $section = null;
        try {
            $section = $this->configProvider->getApiTree($path);
        } catch (ItemNotFoundException $e) {
            // ignore this exception
        }

        return null !== $section;
    }

    /**
     * Gets all configuration data of the specified section
     *
     * @param string $path  The path to API section. For example: look-and-feel/grid
     * @param string $scope The configuration scope
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
     * Gets a configuration variable data from the specified section
     *
     * @param string $key   The key of a configuration variable
     * @param string $path  The path to API section. For example: look-and-feel/grid
     * @param string $scope The configuration scope
     *
     * @return array|null
     */
    public function getDataItem($key, $path, $scope = 'user')
    {
        $variable = $this->configProvider->getApiTree($path)->getVariable($key);
        if (null === $variable) {
            return null;
        }

        $configManager = $this->configManagers[$scope];
        $value = $configManager->get($variable->getKey());
        $dataTransformer = $this->configProvider->getDataTransformer($variable->getKey());
        if ($dataTransformer !== null) {
            $value = $dataTransformer->transform($value);
        }
        $var          = $variable->toArray();
        $var['value'] = $this->getTypedValue($variable->getType(), $value);
        $var          = array_merge($var, $configManager->getInfo($variable->getKey()));

        return $var;
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
