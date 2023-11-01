<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Provider\ProviderInterface;

/**
 * The manager for API configuration tree.
 */
class ConfigApiManager
{
    /** @var ProviderInterface */
    protected $configProvider;

    /** @var ConfigManager[] */
    protected $configManagers;

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
     * Gets the list of keys of a configuration variables.
     *
     * @return string[]
     */
    public function getDataItemKeys()
    {
        $keys = [];
        $this->extractDataItemKeys($keys, $this->configProvider->getApiTree());

        return array_values(array_unique($keys));
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
     * Gets the list of paths for all configuration sections to which the given configuration variable belongs.
     * The result is sorted alphabetically.
     *
     * @param string $key The key of a configuration variable
     *
     * @return string[]
     */
    public function getDataItemSections(string $key)
    {
        $sections = [];
        $this->extractSectionPathsForDataItem($sections, $key, $this->configProvider->getApiTree());
        sort($sections, SORT_FLAG_CASE);

        return $sections;
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
            $path = $this->getSectionPath($subSection, $parentPath);
            $result[] = $path;
            $this->extractSectionPaths($result, $subSection, $path);
        }
    }

    /**
     * Extracts paths of all sections in the given configuration tree
     * to which the given configuration variable belongs.
     */
    private function extractSectionPathsForDataItem(
        array &$result,
        string $dataItemKey,
        SectionDefinition $tree,
        ?string $parentPath = null
    ): void {
        $subSections = $tree->getSubSections();
        foreach ($subSections as $subSection) {
            $path = $this->getSectionPath($subSection, $parentPath);
            $variable = $subSection->getVariable($dataItemKey);
            if (null !== $variable) {
                $result[] = $path;
            }
            $this->extractSectionPathsForDataItem($result, $dataItemKey, $subSection, $path);
        }
    }

    /**
     * Extracts keys of all sections in the given configuration tree.
     */
    private function extractDataItemKeys(array &$result, SectionDefinition $tree): void
    {
        $variables = $tree->getVariables();
        foreach ($variables as $variable) {
            $result[] = $variable->getKey();
        }
        $subSections = $tree->getSubSections();
        foreach ($subSections as $subSection) {
            $this->extractDataItemKeys($result, $subSection);
        }
    }

    private function getSectionPath(SectionDefinition $subSection, ?string $parentPath): string
    {
        return $parentPath
            ? $parentPath . '/' . $subSection->getName()
            : $subSection->getName();
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
