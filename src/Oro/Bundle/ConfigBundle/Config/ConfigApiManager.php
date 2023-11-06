<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Provider\ProviderInterface;

/**
 * The manager for API configuration tree.
 */
class ConfigApiManager
{
    private ProviderInterface $configProvider;
    /** @var ConfigManager[] */
    private array $configManagers;

    public function __construct(ProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function addConfigManager(string $scope, ConfigManager $manager): void
    {
        $this->configManagers[$scope] = $manager;
    }

    public function getConfigManager(string $scope): ?ConfigManager
    {
        return $this->configManagers[$scope] ?? null;
    }

    /**
     * Gets all configuration scopes.
     *
     * @return string[]
     */
    public function getScopes(): array
    {
        return array_keys($this->configManagers);
    }

    /**
     * Gets the list of paths for all configuration sections.
     * The result is sorted alphabetically.
     *
     * @return string[]
     */
    public function getSections(): array
    {
        $sections = [];
        $this->extractSectionPaths($sections, $this->configProvider->getApiTree());
        sort($sections, SORT_FLAG_CASE);

        return $sections;
    }

    /**
     * Checks whether a section with a given path exists.
     *
     * @param string $path The path to API section. For example: look-and-feel/grid
     *
     * @return bool
     */
    public function hasSection(string $path): bool
    {
        $section = null;
        try {
            $section = $this->configProvider->getApiTree($path);
        } catch (ItemNotFoundException) {
            // ignore this exception
        }

        return null !== $section;
    }

    /**
     * Gets the list of keys of a configuration variables.
     *
     * @return string[]
     */
    public function getDataItemKeys(): array
    {
        $keys = [];
        $this->extractDataItemKeys($keys, $this->configProvider->getApiTree());

        return array_values(array_unique($keys));
    }

    /**
     * Gets all configuration data of the specified section.
     *
     * @param string   $path    The path to API section. For example: look-and-feel/grid
     * @param string   $scope   The configuration scope
     * @param int|null $scopeId The configuration scope identifier
     *
     * @return array
     *
     * @throws ItemNotFoundException when the given path is invalid
     */
    public function getData(string $path, string $scope = 'user', int $scopeId = null): array
    {
        $variables = [];
        $this->extractVariables($variables, $this->configProvider->getApiTree($path));

        $result = [];
        $configManager = $this->configManagers[$scope];
        /** @var VariableDefinition $variable */
        foreach ($variables as $variable) {
            $result[] = $this->getVariableValue($variable, $configManager, $scopeId);
        }

        return $result;
    }

    /**
     * Gets a configuration variable data from the specified section.
     *
     * @param string   $key     The key of a configuration variable
     * @param string   $path    The path to API section. For example: look-and-feel/grid
     * @param string   $scope   The configuration scope
     * @param int|null $scopeId The configuration scope identifier
     *
     * @return array|null
     *
     * @throws ItemNotFoundException when the given path is invalid
     */
    public function getDataItem(string $key, string $path, string $scope = 'user', int $scopeId = null): ?array
    {
        $variable = $this->configProvider->getApiTree($path)->getVariable($key);
        if (null === $variable) {
            return null;
        }

        return $this->getVariableValue($variable, $this->configManagers[$scope], $scopeId);
    }

    /**
     * Gets the list of paths for all configuration sections to which the given configuration variable belongs.
     * The result is sorted alphabetically.
     *
     * @param string $key The key of a configuration variable
     *
     * @return string[]
     */
    public function getDataItemSections(string $key): array
    {
        $sections = [];
        $this->extractSectionPathsForDataItem($sections, $key, $this->configProvider->getApiTree());
        sort($sections, SORT_FLAG_CASE);

        return $sections;
    }

    /**
     * Extracts paths of all sections in the given configuration tree.
     */
    private function extractSectionPaths(array &$result, SectionDefinition $tree, ?string $parentPath = null): void
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

    /**
     * Extracts keys of all sections in the given configuration tree.
     */
    private function extractVariables(array &$result, SectionDefinition $tree): void
    {
        $variables = $tree->getVariables();
        foreach ($variables as $variable) {
            $result[$variable->getKey()] = $variable;
        }
        $subSections = $tree->getSubSections();
        foreach ($subSections as $subSection) {
            $this->extractVariables($result, $subSection);
        }
    }

    private function getSectionPath(SectionDefinition $subSection, ?string $parentPath): string
    {
        return $parentPath
            ? $parentPath . '/' . $subSection->getName()
            : $subSection->getName();
    }

    private function getVariableValue(VariableDefinition $variable, ConfigManager $configManager, ?int $scopeId): array
    {
        $value = $configManager->get($variable->getKey(), false, false, $scopeId);
        $dataTransformer = $this->configProvider->getDataTransformer($variable->getKey());
        if ($dataTransformer !== null) {
            $value = $dataTransformer->transform($value);
        }

        $result = $variable->toArray();
        $result['value'] = $this->getTypedValue($variable->getType(), $value);

        return array_merge($result, $configManager->getInfo($variable->getKey(), $scopeId));
    }

    private function getTypedValue(string $type, mixed $value): mixed
    {
        if (null !== $value) {
            if ('boolean' === $type) {
                return (bool)$value;
            }
            if ('integer' === $type) {
                return (int)$value;
            }
        }

        return $value;
    }
}
