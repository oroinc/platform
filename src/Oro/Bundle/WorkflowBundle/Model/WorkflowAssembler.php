<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\WorkflowBundle\Exception\UnknownStepException;
use Oro\Bundle\WorkflowBundle\Exception\AssemblerException;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class WorkflowAssembler extends AbstractAssembler
{
    /**
     * @var
     */
    protected $container;

    /**
     * @var WorkflowConfiguration
     */
    protected $configurationTree;

    /**
     * @var AttributeAssembler
     */
    protected $attributeAssembler;

    /**
     * @var StepAssembler
     */
    protected $stepAssembler;

    /**
     * @var TransitionAssembler
     */
    protected $transitionAssembler;

    /**
     * @param ContainerInterface $container
     * @param WorkflowConfiguration $workflowConfiguration
     * @param AttributeAssembler $attributeAssembler
     * @param StepAssembler $stepAssembler
     * @param TransitionAssembler $transitionAssembler
     */
    public function __construct(
        ContainerInterface $container,
        WorkflowConfiguration $workflowConfiguration,
        AttributeAssembler $attributeAssembler,
        StepAssembler $stepAssembler,
        TransitionAssembler $transitionAssembler
    ) {
        $this->container = $container;
        $this->workflowConfiguration = $workflowConfiguration;
        $this->attributeAssembler = $attributeAssembler;
        $this->stepAssembler = $stepAssembler;
        $this->transitionAssembler = $transitionAssembler;
    }

    /**
     * @param WorkflowDefinition $definition
     * @throws UnknownStepException
     * @throws AssemblerException
     * @return Workflow
     */
    public function assemble(WorkflowDefinition $definition)
    {
        $configuration = $this->parseConfiguration($definition);
        $this->assertOptions(
            $configuration,
            array(
                WorkflowConfiguration::NODE_STEPS,
                WorkflowConfiguration::NODE_TRANSITIONS,
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS
            )
        );

        $attributes = $this->assembleAttributes($definition, $configuration);
        $steps = $this->assembleSteps($definition, $configuration, $attributes);
        $transitions = $this->assembleTransitions($configuration, $steps, $attributes);

        $workflow = $this->createWorkflow();
        $workflow
            ->setName($definition->getName())
            ->setLabel($definition->getLabel())
            ->setEnabled($definition->isEnabled());

        $workflow->getStepManager()->setSteps($steps);
        $workflow->getAttributeManager()->setAttributes($attributes);
        $workflow->getTransitionManager()->setTransitions($transitions);

        $this->validateWorkflow($workflow);

        return $workflow;
    }

    /**
     * @param Workflow $workflow
     * @throws AssemblerException
     */
    protected function validateWorkflow(Workflow $workflow)
    {
        $startTransitions = $workflow->getTransitionManager()->getTransitions()->filter(
            function (Transition $transition) {
                return $transition->isStart();
            }
        );
        if (!$startTransitions->count()) {
            throw new AssemblerException(
                sprintf(
                    'Workflow "%s" does not contains neither start step nor start transitions',
                    $workflow->getName()
                )
            );
        }
    }

    protected function parseConfiguration(WorkflowDefinition $workflowDefinition)
    {
        return $this->prepareDefaultStartTransition(
            $workflowDefinition,
            $this->workflowConfiguration->processConfiguration($workflowDefinition->getConfiguration())
        );
    }

    protected function prepareDefaultStartTransition(WorkflowDefinition $workflowDefinition, $configuration)
    {
        if ($workflowDefinition->getStartStep()
            && !array_key_exists(
                Workflow::DEFAULT_START_TRANSITION_NAME,
                $configuration[WorkflowConfiguration::NODE_TRANSITIONS]
            )
        ) {
            $startTransitionDefinitionName = Workflow::DEFAULT_START_TRANSITION_NAME . '_definition';
            if (!array_key_exists(
                $startTransitionDefinitionName,
                $configuration[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS]
            )) {
                $configuration[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS][$startTransitionDefinitionName] =
                    array();
            }

            $configuration[WorkflowConfiguration::NODE_TRANSITIONS][Workflow::DEFAULT_START_TRANSITION_NAME] =
                array(
                    'label' => $workflowDefinition->getLabel(),
                    'step_to' => $workflowDefinition->getStartStep()->getName(),
                    'is_start' => true,
                    'transition_definition' => $startTransitionDefinitionName
                );
        }

        return $configuration;
    }

    /**
     * @param WorkflowDefinition $definition
     * @param array $configuration
     * @return Attribute[]|Collection
     */
    protected function assembleAttributes(WorkflowDefinition $definition, array $configuration)
    {
        $attributesConfiguration = $this->getOption($configuration, WorkflowConfiguration::NODE_ATTRIBUTES, array());

        return $this->attributeAssembler->assemble($definition, $attributesConfiguration);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param array $configuration
     * @param Collection $attributes
     * @return Step[]|Collection
     */
    protected function assembleSteps(WorkflowDefinition $definition, array $configuration, Collection $attributes)
    {
        $stepsConfiguration = $this->getOption($configuration, WorkflowConfiguration::NODE_STEPS, array());

        return $this->stepAssembler->assemble($definition, $stepsConfiguration, $attributes);
    }

    /**
     * @param array $configuration
     * @param Collection $steps
     * @param Collection $attributes
     * @return Transition[]|Collection
     */
    protected function assembleTransitions(array $configuration, Collection $steps, Collection $attributes)
    {
        $transitionsConfiguration = $this->getOption($configuration, WorkflowConfiguration::NODE_TRANSITIONS, array());
        $transitionDefinitionsConfiguration = $this->getOption(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            array()
        );

        return $this->transitionAssembler->assemble(
            $transitionsConfiguration,
            $transitionDefinitionsConfiguration,
            $steps,
            $attributes
        );
    }

    /**
     * @return Workflow
     */
    protected function createWorkflow()
    {
        return $this->container->get('oro_workflow.prototype.workflow');
    }
}
