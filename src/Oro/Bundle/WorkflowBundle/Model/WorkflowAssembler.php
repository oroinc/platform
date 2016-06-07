<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\WorkflowBundle\Exception\UnknownStepException;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

use Oro\Component\Action\Exception\AssemblerException;
use Oro\Component\Action\Model\AbstractAssembler as BaseAbstractAssembler;

class WorkflowAssembler extends BaseAbstractAssembler
{
    /**
     * @var ContainerInterface
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
     * @var RestrictionAssembler
     */
    protected $restrictionAssembler;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ContainerInterface   $container
     * @param AttributeAssembler   $attributeAssembler
     * @param StepAssembler        $stepAssembler
     * @param TransitionAssembler  $transitionAssembler
     * @param RestrictionAssembler $restrictionAssembler
     * @param TranslatorInterface  $translator
     */
    public function __construct(
        ContainerInterface $container,
        AttributeAssembler $attributeAssembler,
        StepAssembler $stepAssembler,
        TransitionAssembler $transitionAssembler,
        RestrictionAssembler $restrictionAssembler,
        TranslatorInterface $translator
    ) {
        $this->container            = $container;
        $this->attributeAssembler   = $attributeAssembler;
        $this->stepAssembler        = $stepAssembler;
        $this->transitionAssembler  = $transitionAssembler;
        $this->restrictionAssembler = $restrictionAssembler;
        $this->translator           = $translator;
    }

    /**
     * @param WorkflowDefinition $definition
     * @param bool               $needValidation
     *
     * @throws UnknownStepException
     * @throws AssemblerException
     * @return Workflow
     */
    public function assemble(WorkflowDefinition $definition, $needValidation = true)
    {
        $configuration = $this->parseConfiguration($definition);
        $this->assertOptions(
            $configuration,
            [
                WorkflowConfiguration::NODE_STEPS,
                WorkflowConfiguration::NODE_TRANSITIONS
            ]
        );

        $attributes   = $this->assembleAttributes($definition, $configuration);
        $steps        = $this->assembleSteps($configuration, $attributes);
        $transitions  = $this->assembleTransitions($configuration, $steps, $attributes);
        $restrictions = $this->assembleRestrictions($configuration, $steps, $attributes);

        $workflow = $this->createWorkflow();
        $workflow
            ->setName($definition->getName())
            ->setLabel($definition->getLabel())
            ->setDefinition($definition);

        $workflow->getStepManager()
            ->setSteps($steps);
        $workflow->getAttributeManager()
            ->setAttributes($attributes)
            ->setEntityAttributeName($definition->getEntityAttributeName());
        $workflow->getTransitionManager()
            ->setTransitions($transitions);
        $workflow->setRestrictions($restrictions);

        if ($definition->getStartStep()) {
            $startStepName = $definition->getStartStep()->getName();
            $workflow->getStepManager()->setStartStepName($startStepName);
        }

        if ($needValidation) {
            $this->validateWorkflow($workflow);
        }

        return $workflow;
    }

    /**
     * @param Workflow $workflow
     *
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

    /**
     * @param WorkflowDefinition $workflowDefinition
     *
     * @return array
     */
    protected function parseConfiguration(WorkflowDefinition $workflowDefinition)
    {
        return $this->prepareDefaultStartTransition($workflowDefinition, $workflowDefinition->getConfiguration());
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param array              $configuration
     *
     * @return array
     */
    protected function prepareDefaultStartTransition(WorkflowDefinition $workflowDefinition, array $configuration)
    {
        if ($workflowDefinition->getStartStep()
            && !array_key_exists(
                TransitionManager::DEFAULT_START_TRANSITION_NAME,
                $configuration[WorkflowConfiguration::NODE_TRANSITIONS]
            )
        ) {
            $startTransitionDefinitionName = TransitionManager::DEFAULT_START_TRANSITION_NAME . '_definition';
            if (!array_key_exists(
                $startTransitionDefinitionName,
                $configuration[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS]
            )
            ) {
                $configuration[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS][$startTransitionDefinitionName] =
                    [];
            }

            $label = $this->translator->trans(
                'oro.workflow.transition.start',
                ['%workflow%' => $workflowDefinition->getLabel()]
            );

            $configuration[WorkflowConfiguration::NODE_TRANSITIONS][TransitionManager::DEFAULT_START_TRANSITION_NAME] =
                [
                    'label'                 => $label,
                    'step_to'               => $workflowDefinition->getStartStep()->getName(),
                    'is_start'              => true,
                    'is_hidden'             => true,
                    'is_unavailable_hidden' => true,
                    'transition_definition' => $startTransitionDefinitionName,
                ];
        }

        return $configuration;
    }

    /**
     * @param WorkflowDefinition $definition
     * @param array              $configuration
     *
     * @return Attribute[]|Collection
     */
    protected function assembleAttributes(WorkflowDefinition $definition, array $configuration)
    {
        $attributesConfiguration = $this->getOption($configuration, WorkflowConfiguration::NODE_ATTRIBUTES, []);

        return $this->attributeAssembler->assemble($definition, $attributesConfiguration);
    }

    /**
     * @param array      $configuration
     * @param Collection $attributes
     *
     * @return Step[]|Collection
     */
    protected function assembleSteps(array $configuration, Collection $attributes)
    {
        $stepsConfiguration = $this->getOption($configuration, WorkflowConfiguration::NODE_STEPS, []);

        return $this->stepAssembler->assemble($stepsConfiguration, $attributes);
    }

    /**
     * @param array      $configuration
     * @param Collection $steps
     * @param Collection $attributes
     *
     * @return Transition[]|Collection
     */
    protected function assembleTransitions(array $configuration, Collection $steps, Collection $attributes)
    {
        $transitionsConfiguration           = $this->getOption(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITIONS,
            []
        );
        $transitionDefinitionsConfiguration = $this->getOption(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            []
        );

        return $this->transitionAssembler->assemble(
            $transitionsConfiguration,
            $transitionDefinitionsConfiguration,
            $steps,
            $attributes
        );
    }

    /**
     * @param array      $configuration
     * @param Collection $steps
     * @param Collection $attributes
     *
     * @return Restriction[]|Collection
     */
    protected function assembleRestrictions(array $configuration, Collection $steps, Collection $attributes)
    {
        return $this->restrictionAssembler->assemble($configuration, $steps, $attributes);
    }

    /**
     * @return Workflow
     */
    protected function createWorkflow()
    {
        return $this->container->get('oro_workflow.prototype.workflow');
    }
}
