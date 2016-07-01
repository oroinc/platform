<?php

namespace Oro\Bundle\ConfigBundle\Api\Repository;

use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Bundle\ConfigBundle\Config\ConfigApiManager;

class ConfigurationRepository
{
    /** @var ConfigApiManager */
    protected $configManager;

    /**
     * @param ConfigApiManager $configManager
     */
    public function __construct(ConfigApiManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Returns all configuration scopes.
     *
     * @return string[]
     */
    public function getScopes()
    {
        return $this->configManager->getScopes();
    }

    /**
     * Returns ids of all configuration sections.
     *
     * @return string[]
     */
    public function getSectionIds()
    {
        return array_map(
            function ($sectionId) {
                return $this->encodeSectionId($sectionId);
            },
            $this->configManager->getSections()
        );
    }

    /**
     * Finds a configuration section by its id.
     *
     * @param string $sectionId
     * @param string $scope
     *
     * @return ConfigurationSection|null
     */
    public function getSection($sectionId, $scope)
    {
        if (!$this->configManager->hasSection($this->decodeSectionId($sectionId))) {
            return null;
        }

        $section = new ConfigurationSection($sectionId);
        $section->setOptions($this->getSectionOptions($sectionId, $scope));

        return $section;
    }

    /**
     * Returns all configuration options from a given section.
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
     * Returns a configuration option from a given section.
     *
     * @param string $key
     * @param string $sectionId
     * @param string $scope
     *
     * @return ConfigurationOption
     */
    public function getSectionOption($key, $sectionId, $scope)
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
     *
     * @param string $sectionId
     *
     * @return string mixed
     */
    protected function encodeSectionId($sectionId)
    {
        return str_replace('/', '.', $sectionId);
    }

    /**
     * Converts a section identifier from a form that is used in API to its original value.
     *
     * @param string $sectionId
     *
     * @return string mixed
     */
    protected function decodeSectionId($sectionId)
    {
        return str_replace('.', '/', $sectionId);
    }
}
