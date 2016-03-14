<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EntityConfiguration extends AbstractConfigurationSection
{
    /** @var string */
    protected $sectionName;

    /** @var ConfigurationSectionInterface */
    protected $definitionSection;

    /** @var ConfigurationSectionInterface[] */
    protected $extraSections;

    /** @var int */
    protected $maxNestingLevel;

    /**
     * @param string                              $sectionName
     * @param TargetEntityDefinitionConfiguration $definitionSection
     * @param ConfigurationSectionInterface[]     $extraSections
     * @param int                                 $maxNestingLevel
     */
    public function __construct(
        $sectionName,
        TargetEntityDefinitionConfiguration $definitionSection,
        array $extraSections,
        $maxNestingLevel
    ) {
        $this->sectionName       = $sectionName;
        $this->definitionSection = $definitionSection;
        $this->extraSections     = $extraSections;
        $this->maxNestingLevel   = $maxNestingLevel;

        $definitionSection->setParentSectionName($sectionName);
    }

    /**
     * Gets the name of the section.
     *
     * @return string
     */
    public function getSectionName()
    {
        return $this->sectionName;
    }

    /**
     * Builds the definition of an entity configuration.
     *
     * @param NodeBuilder $node
     * @param array       $configureCallbacks
     * @param array       $preProcessCallbacks
     * @param array       $postProcessCallbacks
     * @param string      $currentSectionName
     */
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks,
        $currentSectionName = ''
    ) {
        $node->booleanNode(ConfigUtil::INHERIT)->end();
        $this->configureEntity(
            $node,
            $configureCallbacks,
            $preProcessCallbacks,
            $postProcessCallbacks,
            $currentSectionName
        );
    }

    /**
     * Builds the definition of an entity configuration.
     *
     * @param NodeBuilder $node
     * @param array       $configureCallbacks
     * @param array       $preProcessCallbacks
     * @param array       $postProcessCallbacks
     * @param string      $currentSectionName
     */
    protected function configureEntity(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks,
        $currentSectionName = ''
    ) {
        $this->definitionSection->configure(
            $node,
            $this->getDefinitionConfigureCallbacks($configureCallbacks, $preProcessCallbacks, $postProcessCallbacks),
            $preProcessCallbacks,
            $postProcessCallbacks
        );
        $definitionSectionName = $this->definitionSection->getSectionName();
        foreach ($this->extraSections as $name => $configuration) {
            // check if configuration can be added to the section
            $sectionName = $currentSectionName ?:  $this->sectionName . '.' . $definitionSectionName;
            if (!$configuration->isApplicable($sectionName)) {
                continue;
            }
            $configuration->configure(
                $node->arrayNode($name)->children(),
                $configureCallbacks,
                $preProcessCallbacks,
                $postProcessCallbacks
            );
        }
        /** @var ArrayNodeDefinition $parentSectionNode */
        $parentSectionNode = $node->end();
        $parentSectionNode
            ->validate()
            ->always(
                function ($value) use ($postProcessCallbacks) {
                    return $this->callProcessConfigCallbacks(
                        $this->postProcessConfig($value),
                        $postProcessCallbacks,
                        $this->sectionName
                    );
                }
            );
    }

    /**
     * @param array $configureCallbacks
     * @param array $preProcessCallbacks
     * @param array $postProcessCallbacks
     *
     * @return array
     */
    protected function getDefinitionConfigureCallbacks(
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $definitionConfigureCallbacks = $configureCallbacks;
        if ($this->maxNestingLevel > 0) {
            $fieldSectionName        = $this->sectionName . '.entity.fields';
            $fieldConfigureCallbacks = isset($definitionConfigureCallbacks[$fieldSectionName])
                ? $definitionConfigureCallbacks[$fieldSectionName]
                : [];

            $targetEntityConfiguration = new self(
                $this->sectionName,
                new TargetEntityDefinitionConfiguration(),
                $this->extraSections,
                $this->maxNestingLevel - 1
            );

            $fieldConfigureCallbacks[] = function (NodeBuilder $fieldNode) use (
                $targetEntityConfiguration,
                $configureCallbacks,
                $preProcessCallbacks,
                $postProcessCallbacks,
                $fieldSectionName
            ) {
                $targetEntityConfiguration->configureEntity(
                    $fieldNode,
                    $configureCallbacks,
                    $preProcessCallbacks,
                    $postProcessCallbacks,
                    $fieldSectionName
                );
            };

            $definitionConfigureCallbacks[$fieldSectionName] = $fieldConfigureCallbacks;
        }

        return $definitionConfigureCallbacks;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessConfig(array $config)
    {
        foreach ($this->extraSections as $name => $configuration) {
            if (empty($config[$name])) {
                unset($config[$name]);
            }
        }

        return $config;
    }
}
