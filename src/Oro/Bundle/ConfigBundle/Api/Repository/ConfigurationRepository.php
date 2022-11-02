<?php

namespace Oro\Bundle\ConfigBundle\Api\Repository;

use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Config\ConfigApiManager;

/**
 * The repository to get system configuration.
 */
class ConfigurationRepository
{
    private ConfigApiManager $configManager;

    public function __construct(ConfigApiManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Gets all configuration scopes.
     *
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->configManager->getScopes();
    }

    /**
     * Gets IDs of all configuration sections.
     *
     * @return string[]
     */
    public function getSectionIds(): array
    {
        return array_map(
            function ($sectionId) {
                return $this->encodeSectionId($sectionId);
            },
            $this->configManager->getSections()
        );
    }

    /**
     * Finds a configuration section by its ID.
     */
    public function getSection(string $sectionId, string $scope): ?ConfigurationSection
    {
        if (!$this->configManager->hasSection($this->decodeSectionId($sectionId))) {
            return null;
        }

        $section = new ConfigurationSection($sectionId);
        $section->setOptions($this->getSectionOptions($sectionId, $scope));

        return $section;
    }

    /**
     * Gets all configuration options from a given section.
     *
     * @param string $sectionId
     * @param string $scope
     *
     * @return ConfigurationOption[]
     */
    public function getSectionOptions(string $sectionId, string $scope): array
    {
        $result = [];
        $data = $this->configManager->getData($this->decodeSectionId($sectionId), $scope);
        foreach ($data as $item) {
            $option = new ConfigurationOption($scope, $item['key']);
            $option->setDataType($item['type']);
            $option->setValue($item['value']);
            $option->setCreatedAt($item['createdAt']);
            $option->setUpdatedAt($item['updatedAt']);

            $result[] = $option;
        }

        return $result;
    }

    /**
     * Gets a configuration option from a given section.
     */
    public function getSectionOption(string $key, string $sectionId, string $scope): ConfigurationOption
    {
        $item = $this->configManager->getDataItem($key, $this->decodeSectionId($sectionId), $scope);
        $option = new ConfigurationOption($scope, $item['key']);
        $option->setDataType($item['type']);
        $option->setValue($item['value']);
        $option->setCreatedAt($item['createdAt']);
        $option->setUpdatedAt($item['updatedAt']);

        return $option;
    }

    /**
     * Converts a section identifier to a form that may be used in API.
     */
    private function encodeSectionId(string $sectionId): string
    {
        return str_replace('/', '.', $sectionId);
    }

    /**
     * Converts a section identifier from a form that is used in API to its original value.
     */
    private function decodeSectionId(string $sectionId): string
    {
        return str_replace('.', '/', $sectionId);
    }
}
