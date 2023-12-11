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
    /** @var ConfigApiManager */
    protected $configManager;

    public function __construct(ConfigApiManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Gets all configuration scopes.
     *
     * @return string[]
     */
    public function getScopes()
    {
        return $this->configManager->getScopes();
    }

    /**
     * Gets IDs of all configuration sections.
     *
     * @return string[]
     */
    public function getSectionIds()
    {
        $result = [];
        $sectionIds = $this->configManager->getSections();
        foreach ($sectionIds as $sectionId) {
            $result[] = $this->encodeSectionId($sectionId);
        }

        return $result;
    }

    /**
     * Finds a configuration section by its ID.
     *
     * @param string $sectionId
     * @param string $scope
     * @param bool   $withOptions
     *
     * @return ConfigurationSection|null
     */
    public function getSection($sectionId, $scope, $withOptions = true)
    {
        if (!$this->configManager->hasSection($this->decodeSectionId($sectionId))) {
            return null;
        }

        $section = new ConfigurationSection($sectionId);
        if ($withOptions) {
            $section->setOptions($this->getSectionOptions($sectionId, $scope));
        }

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
    public function getSectionOptions($sectionId, $scope)
    {
        $result = [];
        $data = $this->configManager->getData($this->decodeSectionId($sectionId), $scope);
        foreach ($data as $item) {
            $result[] = $this->createOption($item, $scope);
        }

        return $result;
    }

    /**
     * Gets a configuration option from a given section.
     */
    public function getSectionOption($key, $sectionId, $scope)
    {
        $item = $this->configManager->getDataItem($key, $this->decodeSectionId($sectionId), $scope);

        return $this->createOption($item, $scope);
    }

    /**
     * Gets keys of all configuration options.
     *
     * @return string[]
     */
    public function getOptionKeys()
    {
        return $this->configManager->getDataItemKeys();
    }

    /**
     * Gets a configuration option.
     *
     * @param string $key
     * @param string $scope
     *
     * @return ConfigurationOption|null
     */
    public function getOption($key, $scope)
    {
        $sections = $this->configManager->getDataItemSections($key);
        if (!$sections) {
            return null;
        }

        $item = $this->configManager->getDataItem($key, $sections[0], $scope);

        return $this->createOption($item, $scope);
    }

    private function createOption(array $item, string $scope): ConfigurationOption
    {
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
