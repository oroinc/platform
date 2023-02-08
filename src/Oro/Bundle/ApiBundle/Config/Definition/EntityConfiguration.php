<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The builder for "entities" configuration section.
 */
class EntityConfiguration
{
    private string $sectionName;
    private ConfigurationSectionInterface $definitionSection;
    private ConfigurationSettingsInterface $settings;
    private int $maxNestingLevel;

    public function __construct(
        string $sectionName,
        TargetEntityDefinitionConfiguration $definitionSection,
        ConfigurationSettingsInterface $settings,
        int $maxNestingLevel
    ) {
        $this->sectionName = $sectionName;
        $this->definitionSection = $definitionSection;
        $this->settings = $settings;
        $this->maxNestingLevel = $maxNestingLevel;

        $definitionSection->setParentSectionName($sectionName);

        if ($this->maxNestingLevel > 0) {
            $definitionSectionSettings = new DefinitionConfigurationSettings($settings);
            $definitionSectionSettings->addAdditionalConfigureCallback(
                $this->sectionName . '.entity.field',
                function (NodeBuilder $fieldNode) {
                    $targetEntityConfiguration = new self(
                        $this->sectionName,
                        new TargetEntityDefinitionConfiguration(),
                        $this->settings,
                        $this->maxNestingLevel - 1
                    );
                    $targetEntityConfiguration->configureEntity($fieldNode, $this->sectionName . '.entity.field');
                }
            );
            $this->definitionSection->setSettings($definitionSectionSettings);
        } else {
            $this->definitionSection->setSettings($settings);
        }
    }

    /**
     * Gets the name of the section.
     */
    public function getSectionName(): string
    {
        return $this->sectionName;
    }

    /**
     * Builds the definition of an entity configuration.
     */
    public function configure(NodeBuilder $node, string $currentSectionName = ''): void
    {
        $node->booleanNode(ConfigUtil::INHERIT)->end();
        $this->configureEntity($node, $currentSectionName);
    }

    /**
     * Builds the definition of an entity configuration.
     */
    private function configureEntity(NodeBuilder $node, string $currentSectionName): void
    {
        $this->definitionSection->configure($node);
        $definitionSectionName = $this->definitionSection->getSectionName();
        $extraSections = $this->settings->getExtraSections();
        foreach ($extraSections as $name => $configuration) {
            // check if configuration can be added to the section
            $sectionName = $currentSectionName ?: $this->sectionName . '.' . $definitionSectionName;
            if (!$configuration->isApplicable($sectionName)) {
                continue;
            }
            $configuration->configure($node->arrayNode($name)->children());
        }
        /** @var ArrayNodeDefinition $parentSectionNode */
        $parentSectionNode = $node->end();
        $parentSectionNode
            ->validate()
            ->always(
                function ($value) {
                    return $this->postProcessConfig($value);
                }
            );
    }

    private function postProcessConfig(array $config): array
    {
        $extraSections = $this->settings->getExtraSections();
        foreach ($extraSections as $name => $configuration) {
            if (empty($config[$name])) {
                unset($config[$name]);
            }
        }

        return $config;
    }
}
