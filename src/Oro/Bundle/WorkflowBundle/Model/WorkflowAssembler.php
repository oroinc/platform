<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\UnknownStepException;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Component\Action\Exception\AssemblerException;
use Oro\Component\Action\Model\AbstractAssembler as BaseAbstractAssembler;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds instance of Workflow model based on the related Workflow definition.
 */
class WorkflowAssembler extends BaseAbstractAssembler implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
                WorkflowConfiguration::NODE_TRANSITIONS,
            ]
        );

        $attributes   = $this->assembleAttributes($definition, $configuration);
        $steps        = $this->assembleSteps($configuration, $attributes);
        $transitions  = $this->assembleTransitions($configuration, $steps, $attributes);
        $restrictions = $this->assembleRestrictions($configuration, $steps, $attributes);

        $workflow = $this->createWorkflow();
        $workflow->setDefinition($definition);

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

            $translator = $this->container->get(TranslatorInterface::class);
            $label = $translator->trans(
                'oro.workflow.transition.start',
                ['%workflow%' => $translator->trans(
                    $workflowDefinition->getLabel(),
                    [],
                    WorkflowTranslationHelper::TRANSLATION_DOMAIN
                )]
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
        $transitionConfiguration = $this->getOption($configuration, WorkflowConfiguration::NODE_TRANSITIONS);

        return $this->container->get(AttributeAssembler::class)
            ->assemble($definition, $attributesConfiguration, $transitionConfiguration);
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

        return $this->container->get(StepAssembler::class)->assemble($stepsConfiguration, $attributes);
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
        return $this->container->get(TransitionAssembler::class)->assemble($configuration, $steps, $attributes);
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
        return $this->container->get(RestrictionAssembler::class)->assemble($configuration, $steps, $attributes);
    }

    /**
     * Workflow service not shared, new instance created for each call
     */
    protected function createWorkflow(): Workflow
    {
        return $this->container->get(Workflow::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            Workflow::class,
            AttributeAssembler::class,
            StepAssembler::class,
            TransitionAssembler::class,
            RestrictionAssembler::class,
            TranslatorInterface::class,
        ];
    }
}
