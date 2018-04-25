<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\Action\Exception\AssemblerException;
use Oro\Component\Action\Model\AbstractAssembler as BaseAbstractAssembler;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class TransitionAssembler extends BaseAbstractAssembler
{
    /** @var FormOptionsAssembler */
    protected $formOptionsAssembler;

    /** @var ConditionFactory */
    protected $conditionFactory;

    /** @var ActionFactoryInterface */
    protected $actionFactory;

    /** @var FormOptionsConfigurationAssembler */
    protected $formOptionsConfigurationAssembler;

    /** @var TransitionOptionsResolver */
    protected $optionsResolver;

    /**
     * @param FormOptionsAssembler $formOptionsAssembler
     * @param ConditionFactory $conditionFactory
     * @param ActionFactoryInterface $actionFactory
     * @param FormOptionsConfigurationAssembler $formOptionsConfigurationAssembler
     * @param TransitionOptionsResolver $optionsResolver
     */
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
            $this->assertOptions($options, array('transition_definition'));
            $definitionName = $options['transition_definition'];
            if (!isset($definitions[$definitionName])) {
                throw new AssemblerException(
                    sprintf('Unknown transition definition %s', $definitionName)
                );
            }

            $definition = $definitions[$definitionName];

            $transition = $this->assembleTransition($name, $options, $definition, $steps, $attributes);
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
        $definitions = array();
        foreach ($configuration as $name => $options) {
            if (empty($options)) {
                $options = array();
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
     * @param Step[]|ArrayCollection $steps
     * @param Attribute[]|Collection $attributes
     * @return Transition
     * @throws AssemblerException
     */
    protected function assembleTransition($name, array $options, array $definition, $steps, $attributes)
    {
        $this->assertOptions($options, array('step_to'));
        $stepToName = $options['step_to'];
        if (empty($steps[$stepToName])) {
            throw new AssemblerException(sprintf('Step "%s" not found', $stepToName));
        }

        $transition = new Transition($this->optionsResolver);
        $transition->setName($name)
            ->setStepTo($steps[$stepToName])
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

        if (!empty($definition['preactions'])) {
            $preAction = $this->actionFactory->create(ConfigurableAction::ALIAS, $definition['preactions']);
            $transition->setPreAction($preAction);
        }

        $definition['preconditions'] = $this->addAclPreConditions($options, $definition, $name);

        if (!empty($definition['preconditions'])) {
            $condition = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $definition['preconditions']);
            $transition->setPreCondition($condition);
        }

        if (!empty($definition['conditions'])) {
            $condition = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $definition['conditions']);
            $transition->setCondition($condition);
        }

        $this->processActions($transition, $definition['actions']);

        if (!empty($options['schedule'])) {
            $transition->setScheduleCron($this->getOption($options['schedule'], 'cron', null));
            $transition->setScheduleFilter($this->getOption($options['schedule'], 'filter', null));
            $transition->setScheduleCheckConditions(
                $this->getOption($options['schedule'], 'check_conditions_before_job_creation', false)
            );
        }

        if (!empty($options['form_options'][WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION])) {
            $this->formOptionsConfigurationAssembler->assemble($options);
        }
        return $transition;
    }

    /**
     * @param Transition $transition
     * @param array $options
     */
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

    /**
     * @param Transition $transition
     * @param array $actions
     */
    protected function processActions(Transition $transition, array $actions)
    {
        if ($transition->getDisplayType() === WorkflowConfiguration::TRANSITION_DISPLAY_TYPE_PAGE) {
            $actions = array_merge([
                [
                    '@resolve_destination_page' => $transition->getDestinationPage(),
                ],
            ], $actions);
        }

        if (empty($actions)) {
            return;
        }

        $transition->setAction($this->actionFactory->create(ConfigurableAction::ALIAS, $actions));
    }

    /**
     * @param array  $options
     * @param array  $definition
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
             */
            $aclPreCondition = ['@acl_granted' => $aclPreConditionDefinition];

            if (empty($definition['preconditions'])) {
                $definition['preconditions'] = $aclPreCondition;
            } else {
                $definition['preconditions'] = [
                    '@and' => [
                        $aclPreCondition,
                        $definition['preconditions']
                    ]
                ];
            }
        }

        /**
         * @see IsGrantedWorkflowTransition
         */
        $precondition = [
            '@is_granted_workflow_transition' => [
                'parameters' => [
                    $transitionName,
                    $this->getOption($options, 'step_to')
                ]
            ]
        ];
        if (empty($definition['preconditions'])) {
            $definition['preconditions'] = $precondition;
        } else {
            $definition['preconditions'] = [
                '@and' => [
                    $precondition,
                    $definition['preconditions']
                ]
            ];
        }

        return !empty($definition['preconditions']) ? $definition['preconditions'] : [];
    }

    /**
     * @param array $options
     * @param Attribute[]|Collection $attributes
     * @param string $transitionName
     * @return array
     */
    protected function assembleFormOptions(array $options, $attributes, $transitionName)
    {
        $formOptions = $this->getOption($options, 'form_options', array());
        return $this->formOptionsAssembler->assemble($formOptions, $attributes, 'transition', $transitionName);
    }
}
