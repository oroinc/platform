<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

/**
 * The builder for workflow definitions.
 */
class WorkflowDefinitionConfigurationBuilder
{
    /** @var WorkflowAssembler */
    private $workflowAssembler;

    /** @var iterable|WorkflowDefinitionBuilderExtensionInterface[] */
    private $extensions;

    /**
     * @param WorkflowAssembler                                      $workflowAssembler
     * @param iterable|WorkflowDefinitionBuilderExtensionInterface[] $extensions
     */
    public function __construct(WorkflowAssembler $workflowAssembler, iterable $extensions)
    {
        $this->workflowAssembler = $workflowAssembler;
        $this->extensions = $extensions;
    }

    /**
     * @param array $configurationData
     *
     * @return WorkflowDefinition[]
     */
    public function buildFromConfiguration(array $configurationData)
    {
        $workflowDefinitions = [];
        foreach ($configurationData as $workflowName => $workflowConfiguration) {
            $workflowDefinitions[] = $this->buildOneFromConfiguration($workflowName, $workflowConfiguration);
        }

        return $workflowDefinitions;
    }

    /**
     * @param string $name
     * @param array  $configuration
     *
     * @return WorkflowDefinition
     */
    public function buildOneFromConfiguration($name, array $configuration)
    {
        foreach ($this->extensions as $extension) {
            $configuration = $extension->prepare($name, $configuration);
        }

        if (!isset($configuration['entity'])) {
            throw new MissedRequiredOptionException('The "entity" configuration option is required.');
        }

        $system = $configuration['is_system'] ?? false;
        $startStepName = $configuration['start_step'] ?? null;
        $entityAttributeName = $configuration['entity_attribute'] ?? WorkflowConfiguration::DEFAULT_ENTITY_ATTRIBUTE;
        $stepsDisplayOrdered = $configuration['steps_display_ordered'] ?? false;
        $activeGroups = $configuration[WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS] ?? [];
        $recordGroups = $configuration[WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS] ?? [];
        $applications = $configuration[WorkflowConfiguration::NODE_APPLICATIONS]
            ?? [CurrentApplicationProviderInterface::DEFAULT_APPLICATION];

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition
            ->setName($name)
            ->setLabel($configuration['label'])
            ->setRelatedEntity($configuration['entity'])
            ->setStepsDisplayOrdered($stepsDisplayOrdered)
            ->setSystem($system)
            ->setActive($configuration['defaults']['active'])
            ->setPriority($configuration['priority'])
            ->setEntityAttributeName($entityAttributeName)
            ->setExclusiveActiveGroups($activeGroups)
            ->setExclusiveRecordGroups($recordGroups)
            ->setApplications($applications)
            ->setConfiguration($this->filterConfiguration($configuration));

        $workflow = $this->workflowAssembler->assemble($workflowDefinition, false);

        $this->processInitContext($workflow, $workflowDefinition);

        $this->setSteps($workflowDefinition, $workflow);
        $workflowDefinition->setStartStep($workflowDefinition->getStepByName($startStepName));

        $this->setEntityAcls($workflowDefinition, $workflow);
        $this->setEntityRestrictions($workflowDefinition, $workflow);

        return $workflowDefinition;
    }

    private function setSteps(WorkflowDefinition $workflowDefinition, Workflow $workflow)
    {
        $workflowSteps = [];
        foreach ($workflow->getStepManager()->getSteps() as $step) {
            $workflowStep = new WorkflowStep();
            $workflowStep
                ->setName($step->getName())
                ->setLabel($step->getLabel())
                ->setStepOrder($step->getOrder())
                ->setFinal($step->isFinal());

            $workflowSteps[] = $workflowStep;
        }

        $workflowDefinition->setSteps($workflowSteps);
    }

    private function setEntityAcls(WorkflowDefinition $workflowDefinition, Workflow $workflow)
    {
        $entityAcls = [];
        foreach ($workflow->getAttributeManager()->getEntityAttributes() as $attribute) {
            foreach ($workflow->getStepManager()->getSteps() as $step) {
                $updatable = $attribute->isEntityUpdateAllowed()
                    && $step->isEntityUpdateAllowed($attribute->getName());
                $deletable = $attribute->isEntityDeleteAllowed()
                    && $step->isEntityDeleteAllowed($attribute->getName());

                if (!$updatable || !$deletable) {
                    $entityAcl = new WorkflowEntityAcl();
                    $entityAcl
                        ->setAttribute($attribute->getName())
                        ->setStep($workflowDefinition->getStepByName($step->getName()))
                        ->setEntityClass($attribute->getOption('class'))
                        ->setUpdatable($updatable)
                        ->setDeletable($deletable);
                    $entityAcls[] = $entityAcl;
                }
            }
        }

        $workflowDefinition->setEntityAcls($entityAcls);
    }

    private function setEntityRestrictions(WorkflowDefinition $workflowDefinition, Workflow $workflow)
    {
        $restrictions = $workflow->getRestrictions();
        $workflowRestrictions = [];
        foreach ($restrictions as $restriction) {
            $workflowRestriction = new WorkflowRestriction();
            $workflowRestriction
                ->setField($restriction->getField())
                ->setAttribute($restriction->getAttribute())
                ->setEntityClass($restriction->getEntity())
                ->setMode($restriction->getMode())
                ->setValues($restriction->getValues())
                ->setStep($workflowDefinition->getStepByName($restriction->getStep()));

            $workflowRestrictions[] = $workflowRestriction;
        }
        $workflowDefinition->setRestrictions($workflowRestrictions);
    }

    /**
     * Collects init context of all start transitions.
     */
    private function processInitContext(Workflow $workflow, WorkflowDefinition $definition)
    {
        $initData = [];
        foreach ($workflow->getTransitionManager()->getStartTransitions() as $startTransition) {
            foreach ($startTransition->getInitEntities() as $entity) {
                $initData[WorkflowConfiguration::NODE_INIT_ENTITIES][$entity][] = $startTransition->getName();
            }
            foreach ($startTransition->getInitRoutes() as $route) {
                $initData[WorkflowConfiguration::NODE_INIT_ROUTES][$route][] = $startTransition->getName();
            }
            foreach ($startTransition->getInitDatagrids() as $datagrid) {
                $initData[WorkflowConfiguration::NODE_INIT_DATAGRIDS][$datagrid][] = $startTransition->getName();
            }
        }
        $definition->setConfiguration(array_merge($definition->getConfiguration(), $initData));
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    private function filterConfiguration(array $configuration)
    {
        $configurationKeys = [
            WorkflowDefinition::CONFIG_SCOPES,
            WorkflowDefinition::CONFIG_DATAGRIDS,
            WorkflowDefinition::CONFIG_FORCE_AUTOSTART,
            WorkflowConfiguration::NODE_DISABLE_OPERATIONS,
            WorkflowConfiguration::NODE_STEPS,
            WorkflowConfiguration::NODE_ATTRIBUTES,
            WorkflowConfiguration::NODE_TRANSITIONS,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            WorkflowConfiguration::NODE_ENTITY_RESTRICTIONS,
            WorkflowConfiguration::NODE_INIT_ENTITIES,
            WorkflowConfiguration::NODE_INIT_ROUTES,
            WorkflowConfiguration::NODE_INIT_DATAGRIDS,
            WorkflowConfiguration::NODE_VARIABLES,
            WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS,
        ];

        return array_intersect_key($configuration, array_flip($configurationKeys));
    }
}
