<?php

namespace Oro\Bundle\WorkflowBundle\Handler\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDefinitionClonerHelper as WDCHelper;

class WorkflowDefinitionCloner
{
    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $source
     */
    public static function mergeDefinition(WorkflowDefinition $definition, WorkflowDefinition $source)
    {
        self::copyMainFields($definition, $source)
            ->setSteps($source->getSteps())
            ->setStartStep($source->getStartStep())
            ->setEntityAcls($source->getEntityAcls())
            ->setRestrictions($source->getRestrictions());
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowDefinition
     */
    public static function cloneDefinition(WorkflowDefinition $definition)
    {
        $steps = self::copySteps($definition->getSteps());

        $newDefinition = self::copyMainFields(new WorkflowDefinition(), $definition)->setSteps($steps);

        $startStep = $definition->getStartStep();
        $startStep = $steps->get($startStep ? $startStep->getName() : null);

        $entityAcls = self::copyEntityAcls($definition);
        $restrictions = self::copyRestrictions($definition);

        return $newDefinition->setStartStep($startStep)->setEntityAcls($entityAcls)->setRestrictions($restrictions);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $source
     * @return WorkflowDefinition
     */
    private static function copyMainFields(WorkflowDefinition $definition, WorkflowDefinition $source)
    {
        $mergedConfiguration = self::copyConfigurationVariables($definition, $source);

        $definition
            ->setName($source->getName())
            ->setLabel($source->getLabel())
            ->setRelatedEntity($source->getRelatedEntity())
            ->setEntityAttributeName($source->getEntityAttributeName())
            ->setConfiguration($mergedConfiguration)
            ->setStepsDisplayOrdered($source->isStepsDisplayOrdered())
            ->setSystem($source->isSystem())
            ->setPriority($source->getPriority())
            ->setExclusiveActiveGroups($source->getExclusiveActiveGroups())
            ->setExclusiveRecordGroups($source->getExclusiveRecordGroups())
            ->setApplications($source->getApplications());

        return $definition;
    }

    /**
     * Copy variable configuration
     *
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $source
     *
     * @return array
     */
    private static function copyConfigurationVariables(WorkflowDefinition $definition, WorkflowDefinition $source)
    {
        $definitionsNode = WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS;
        $variablesNode = WorkflowConfiguration::NODE_VARIABLES;

        $newConfig = $source->getConfiguration();
        $existingConfig = $definition->getConfiguration();
        if (!isset($newConfig[$definitionsNode], $existingConfig[$definitionsNode])) {
            return $newConfig;
        }

        $newDefinition = $newConfig[$definitionsNode];
        $existingDefinition = $existingConfig[$definitionsNode];
        if (!isset($newDefinition[$variablesNode], $existingDefinition[$variablesNode])) {
            return $newConfig;
        }

        $newVariables = $newDefinition[$variablesNode];
        $existingVariables = $existingDefinition[$variablesNode];

        return self::mergeConfigurationVariablesValue($newConfig, $existingVariables, $newVariables);
    }

    /**
     * Retain variables value if:
     *  - 'type' didn't change
     *
     * And in case of objects and entities:
     *  - 'class' didn't change
     *
     * And in case of entities:
     *  - 'options.identifier' didn't change
     *
     * @param array $configuration
     * @param array $definition
     * @param array $source
     *
     * @return array
     */
    private static function mergeConfigurationVariablesValue(array $configuration, $definition, $source)
    {
        $definitionsNode = WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS;
        $variablesNode = WorkflowConfiguration::NODE_VARIABLES;

        $sourceParsed = WDCHelper::parseVariableDefinitions($source);
        $definitionParsed = WDCHelper::parseVariableDefinitions($definition);

        foreach ($sourceParsed as $name => $sourceVariable) {
            // nothing to copy
            if (!isset($definitionParsed[$name])) {
                continue;
            }

            $existingVariable = $definitionParsed[$name];
            if (self::isVariableValueRetainable($existingVariable, $sourceVariable)) {
                $sourceVariable['value'] = WDCHelper::getOption('value', $existingVariable);
                $configuration[$definitionsNode][$variablesNode][$name] = $sourceVariable;
            }
        }

        return $configuration;
    }

    /**
     * @param array $definition
     * @param array $source
     *
     * @return bool
     */
    private static function isVariableValueRetainable($definition, $source)
    {
        // types don't match
        $newType = WDCHelper::getOption('type', $source);
        if ($newType !== WDCHelper::getOption('type', $definition)) {
            return false;
        }

        if (!in_array($newType, ['object', 'entity'], true)) {
            return true;
        }

        // class is not defined or changed
        $newClass = WDCHelper::getOption('options.class', $source);
        $existingClass = WDCHelper::getOption('options.class', $definition);

        if (!$newClass || !$existingClass || $newClass !== $existingClass) {
            return false;
        }

        if ('entity' !== $newType) {
            return true;
        }

        // entity identifier is not defined or changed
        $newIdentifier = WDCHelper::getOption('options.identifier', $source);
        $existingIdentifier = WDCHelper::getOption('options.identifier', $definition);

        return !(!$newClass || !$existingClass || $newIdentifier !== $existingIdentifier);
    }

    /**
     * @param Collection|WorkflowStep[] $steps
     * @return ArrayCollection
     */
    private static function copySteps(Collection $steps)
    {
        $newSteps = new ArrayCollection();
        foreach ($steps as $step) {
            $newStep = new WorkflowStep();
            $newStep->import($step);

            $newSteps->set($newStep->getName(), $newStep);
        }

        return $newSteps;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return ArrayCollection
     */
    private static function copyEntityAcls(WorkflowDefinition $definition)
    {
        $newEntityAcls = new ArrayCollection();
        foreach ($definition->getEntityAcls() as $entityAcl) {
            $newEntityAcl = new WorkflowEntityAcl();
            $newEntityAcl->setDefinition($definition)->import($entityAcl);

            $newEntityAcls->add($newEntityAcl);
        }

        return $newEntityAcls;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return ArrayCollection
     */
    private static function copyRestrictions(WorkflowDefinition $definition)
    {
        $newsRestrictions = new ArrayCollection();
        foreach ($definition->getRestrictions() as $restriction) {
            $newsRestriction = new WorkflowRestriction();
            $newsRestriction->setDefinition($definition)->import($restriction);

            $newsRestrictions->add($newsRestriction);
        }

        return $newsRestrictions;
    }
}
