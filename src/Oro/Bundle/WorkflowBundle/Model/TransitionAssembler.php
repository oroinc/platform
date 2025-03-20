<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionAssembleEvent;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\Action\Exception\AssemblerException;
use Oro\Component\Action\Model\AbstractAssembler as BaseAbstractAssembler;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Assemble transition based on a given configuration.
 */
class TransitionAssembler extends BaseAbstractAssembler
{
    protected FormOptionsAssembler $formOptionsAssembler;
    protected ConditionFactory $conditionFactory;
    protected ActionFactoryInterface $actionFactory;
    protected FormOptionsConfigurationAssembler $formOptionsConfigurationAssembler;
    protected TransitionOptionsResolver $optionsResolver;
    protected ServiceProviderInterface $transitionServiceLocator;
    protected EventDispatcher $eventDispatcher;
    protected TranslatorInterface $translator;

    public function __construct(
        FormOptionsAssembler $formOptionsAssembler,
        ConditionFactory $conditionFactory,
        ActionFactoryInterface $actionFactory,
        FormOptionsConfigurationAssembler $formOptionsConfigurationAssembler,
        TransitionOptionsResolver $optionsResolver
    ) {
        $this->formOptionsAssembler = $formOptionsAssembler;
        $this->conditionFactory = $conditionFactory;
        $this->actionFactory = $actionFactory;
        $this->formOptionsConfigurationAssembler = $formOptionsConfigurationAssembler;
        $this->optionsResolver = $optionsResolver;
    }

    public function setEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function setTransitionServiceLocator(ServiceProviderInterface $transitionServiceLocator): void
    {
        $this->transitionServiceLocator = $transitionServiceLocator;
    }

    /**
     * @param array $configuration
     * @param Step[]|Collection $steps
     * @param Attribute[]|Collection $attributes
     * @return Collection
     * @throws AssemblerException
     */
    public function assemble(array $configuration, $steps, $attributes)
    {
        $transitionsConfiguration = $this->getOption(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITIONS,
            []
        );
        $transitionDefinitionsConfiguration = $this->getOption(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            []
        );

        $definitions = $this->parseDefinitions($transitionDefinitionsConfiguration);

        $transitions = new ArrayCollection();
        foreach ($transitionsConfiguration as $name => $options) {
            $definition = $this->getTransitionDefinition($options, $definitions);

            $event = new TransitionAssembleEvent($name, $options, $definition, $steps, $attributes);
            $this->eventDispatcher->dispatchRaw($event, $event::NAME);
            $transition = $this->assembleTransition($name, $event->getOptions(), $definition, $steps, $attributes);
            $transitions->set($name, $transition);
        }

        return $transitions;
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function parseDefinitions(array $configuration)
    {
        $definitions = [];
        foreach ($configuration as $name => $options) {
            if (empty($options)) {
                $options = [];
            }
            $definitions[$name] = [
                'preactions' => $this->getOption($options, 'preactions', []),
                'preconditions' => $this->getOption($options, 'preconditions', []),
                'conditions' => $this->getOption($options, 'conditions', []),
                'actions' => $this->getOption($options, 'actions', []),
            ];
        }

        return $definitions;
    }

    /**
     * @param string $name
     * @param array $options
     * @param array $definition
     * @param Step[]|array|Collection $steps
     * @param Attribute[]|array|Collection $attributes
     * @return Transition
     * @throws AssemblerException
     */
    protected function assembleTransition($name, array $options, array $definition, $steps, $attributes)
    {
        $transitionServiceName = $this->getOption($options, 'transition_service', null);
        $transitionService = null;
        if ($transitionServiceName) {
            $transitionService = $this->transitionServiceLocator->get($transitionServiceName);
            if ($transitionService instanceof ResetInterface) {
                $transitionService->reset();
            }
        }

        $this->assertOptions($options, array('step_to'));
        $stepToName = $options['step_to'];
        if (empty($steps[$stepToName])) {
            throw new AssemblerException(sprintf('Step "%s" not found', $stepToName));
        }

        $transition = new Transition($this->optionsResolver);
        $transition->setEventDispatcher($this->eventDispatcher);
        $transition->setTranslator($this->translator);
        $transition->setName($name)
            ->setStepTo($steps[$stepToName])
            ->setAclResource($this->getOption($options, 'acl_resource'))
            ->setAclMessage($this->getOption($options, 'acl_message'))
            ->setTransitionService($transitionService)
            ->setLabel($this->getOption($options, 'label'))
            ->setButtonLabel($this->getOption($options, 'button_label'))
            ->setButtonTitle($this->getOption($options, 'button_title'))
            ->setStart($this->getOption($options, 'is_start', false))
            ->setHidden($this->getOption($options, 'is_hidden', false))
            ->setUnavailableHidden($this->getOption($options, 'is_unavailable_hidden', false))
            ->setFormType($this->getOption($options, 'form_type', WorkflowTransitionType::class))
            ->setFormOptions($this->assembleFormOptions($options, $attributes, $name))
            ->setDisplayType(
                $this->getOption($options, 'display_type', WorkflowConfiguration::DEFAULT_TRANSITION_DISPLAY_TYPE)
            )
            ->setDestinationPage($this->getOption($options, 'destination_page'))
            ->setPageTemplate($this->getOption($options, 'page_template'))
            ->setDialogTemplate($this->getOption($options, 'dialog_template'))
            ->setInitEntities($this->getOption($options, WorkflowConfiguration::NODE_INIT_ENTITIES, []))
            ->setInitRoutes($this->getOption($options, WorkflowConfiguration::NODE_INIT_ROUTES, []))
            ->setInitDatagrids($this->getOption($options, WorkflowConfiguration::NODE_INIT_DATAGRIDS, []))
            ->setInitContextAttribute($this->getOption($options, WorkflowConfiguration::NODE_INIT_CONTEXT_ATTRIBUTE));

        $this->processFrontendOptions($transition, $options);
        $this->processDefinition($transition, $definition);
        $this->processSchedule($transition, $options);
        $this->processFormOptions($options);
        $this->processConditionalSteps($transition, $options, $stepToName, $steps);

        return $transition;
    }

    protected function processFrontendOptions(Transition $transition, array $options)
    {
        $frontendOptions = $this->getOption($options, 'frontend_options', []);

        if ($this->getOption($options, 'message')) {
            $frontendOptions['message'] = array_merge(
                $this->getOption($frontendOptions, 'message', []),
                [
                    'content' => $this->getOption($options, 'message'),
                    'message_parameters' => $this->getOption($options, 'message_parameters', []),
                ]
            );
        }

        $transition
            ->setMessage($this->getOption($options, 'message'))
            ->setFrontendOptions($frontendOptions);
    }

    protected function processActions(Transition $transition, array $actions)
    {
        if (empty($actions)) {
            return;
        }

        $transition->setAction($this->actionFactory->create(ConfigurableAction::ALIAS, $actions));
    }

    /**
     * @deprecated Logic was moved to TransitionAclResourceListener::onPreAnnounce and self::processConditionalSteps
     *
     * @param array $options
     * @param array $definition
     * @param string $transitionName
     * @return array
     */
    protected function addAclPreConditions(array $options, array $definition, $transitionName)
    {
        $aclResource = $this->getOption($options, 'acl_resource');

        if ($aclResource) {
            $aclPreConditionDefinition = ['parameters' => $aclResource];
            $aclMessage = $this->getOption($options, 'acl_message');
            if ($aclMessage) {
                $aclPreConditionDefinition['message'] = $aclMessage;
            }

            /**
             * @see AclGranted
             *
             * Note! This logic has been moved to TransitionAclResourceListener
             */
            $definition['preconditions'] = $this->addCondition(
                ['@acl_granted' => $aclPreConditionDefinition],
                $definition['preconditions'] ?? []
            );
        }

        /**
         * @see IsGrantedWorkflowTransition
         */
        $definition['preconditions'] = $this->addCondition(
            $this->getStepAclCheckCondition($transitionName, $this->getOption($options, 'step_to')),
            $definition['preconditions'] ?? []
        );

        return !empty($definition['preconditions']) ? $definition['preconditions'] : [];
    }

    private function addCondition(array $conditions, array $newCondition): array
    {
        return empty($conditions) ? $newCondition : ['@and' => [$newCondition, $conditions]];
    }

    /**
     * @param array $options
     * @param Attribute[]|Collection $attributes
     * @param string $transitionName
     * @return array
     */
    protected function assembleFormOptions(array $options, $attributes, $transitionName)
    {
        $formOptions = $this->getOption($options, 'form_options', []);

        return $this->formOptionsAssembler->assemble($formOptions, $attributes, 'transition', $transitionName);
    }

    protected function processDefinition(Transition $transition, array $definition): void
    {
        if (!$definition) {
            return;
        }

        if (!empty($definition['preactions'])) {
            $preAction = $this->actionFactory->create(ConfigurableAction::ALIAS, $definition['preactions']);
            $transition->setPreAction($preAction);
        }

        if (!empty($definition['preconditions'])) {
            $condition = $this->conditionFactory->create(
                ConfigurableCondition::ALIAS,
                $definition['preconditions']
            );
            $transition->setPreCondition($condition);
        }

        if (!empty($definition['conditions'])) {
            $condition = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $definition['conditions']);
            $transition->setCondition($condition);
        }

        $this->processActions($transition, $definition['actions'] ?? []);
    }

    protected function processSchedule(Transition $transition, array $options): void
    {
        if (empty($options['schedule'])) {
            return;
        }

        $transition->setScheduleCron($this->getOption($options['schedule'], 'cron', null));
        $transition->setScheduleFilter($this->getOption($options['schedule'], 'filter', null));
        $transition->setScheduleCheckConditions(
            $this->getOption($options['schedule'], 'check_conditions_before_job_creation', false)
        );
    }

    protected function processFormOptions(array $options): void
    {
        if (empty($options['form_options'][WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION])) {
            return;
        }

        $this->formOptionsConfigurationAssembler->assemble($options);
    }

    protected function getTransitionDefinition(array $options, array $definitions): array
    {
        if (!empty($options['transition_service'])) {
            return [];
        }
        if (empty($options['transition_definition'])) {
            return [];
        }

        $definitionName = $options['transition_definition'];
        if (!isset($definitions[$definitionName])) {
            throw new AssemblerException(
                sprintf('Unknown transition definition %s', $definitionName)
            );
        }

        return $definitions[$definitionName];
    }

    protected function processConditionalSteps(
        Transition $transition,
        array $options,
        string $stepToName,
        array|Collection $steps
    ): void {
        if (empty($options['conditional_steps_to'])) {
            return;
        }

        $stepsTo = $options['conditional_steps_to'];
        // Add default step_to to a list of conditional steps to correctly check step ACL.
        $stepsTo[$stepToName] = [];

        foreach ($stepsTo as $stepName => $conditionConfig) {
            $conditions = $conditionConfig['conditions'] ?? [];
            $conditions = $this->addCondition(
                $conditions,
                $this->getStepAclCheckCondition($transition->getName(), $stepName)
            );

            $condition = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $conditions);
            $transition->addConditionalStepTo($steps[$stepName], $condition);
        }
    }

    /**
     * @see IsGrantedWorkflowTransition
     */
    private function getStepAclCheckCondition(string $transitionName, string $stepName): array
    {
        return [
            '@is_granted_workflow_transition' => ['parameters' => [$transitionName, $stepName]]
        ];
    }
}
