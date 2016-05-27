<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;
use Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\InitActionsListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Event\TransitionsAttributeEvent;

use Oro\Component\Action\Model\ContextAccessor;

class WorkflowAttributesType extends AbstractType
{
    const NAME = 'oro_workflow_attributes';

    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    /**
     * @var AttributeGuesser
     */
    protected $attributeGuesser;

    /**
     * @var DefaultValuesListener
     */
    protected $defaultValuesListener;

    /**
     * @var InitActionsListener
     */
    protected $initActionsListener;

    /**
     * @var RequiredAttributesListener
     */
    protected $requiredAttributesListener;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param AttributeGuesser $attributeGuesser ,
     * @param DefaultValuesListener $defaultValuesListener
     * @param InitActionsListener $initActionsListener
     * @param RequiredAttributesListener $requiredAttributesListener
     * @param ContextAccessor $contextAccessor
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        WorkflowRegistry $workflowRegistry,
        AttributeGuesser $attributeGuesser,
        DefaultValuesListener $defaultValuesListener,
        InitActionsListener $initActionsListener,
        RequiredAttributesListener $requiredAttributesListener,
        ContextAccessor $contextAccessor,
        EventDispatcherInterface $dispatcher
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->attributeGuesser = $attributeGuesser;
        $this->defaultValuesListener = $defaultValuesListener;
        $this->initActionsListener = $initActionsListener;
        $this->requiredAttributesListener = $requiredAttributesListener;
        $this->contextAccessor = $contextAccessor;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addEventListeners($builder, $options);
        $this->addAttributes($builder, $options);
    }

    /**
     * Adds required event listeners
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    protected function addEventListeners(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['attribute_default_values'])) {
            $this->defaultValuesListener->initialize(
                $options['workflow_item'],
                $options['attribute_default_values']
            );
            $builder->addEventSubscriber($this->defaultValuesListener);
        }

        if (!empty($options['init_actions'])) {
            $this->initActionsListener->initialize(
                $options['workflow_item'],
                $options['init_actions']
            );
            $builder->addEventSubscriber($this->initActionsListener);
        }

        if (!empty($options['attribute_fields'])) {
            $this->requiredAttributesListener->initialize(array_keys($options['attribute_fields']));
            $builder->addEventSubscriber($this->requiredAttributesListener);
        }
    }

    /**
     * Add attributes to form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws InvalidConfigurationException When attribute is not found in given Workflow
     */
    protected function addAttributes(FormBuilderInterface $builder, array $options)
    {
        /** @var Workflow $workflow */
        $workflow = $options['workflow'];

        foreach ($options['attribute_fields'] as $attributeName => $attributeOptions) {
            $attribute = $workflow->getAttributeManager()->getAttribute($attributeName);
            if (!$attribute) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'Invalid reference to unknown attribute "%s" of workflow "%s".',
                        $attributeName,
                        $workflow->getName()
                    )
                );
            }
            if (null === $attributeOptions) {
                $attributeOptions = array();
            }
            $this->addAttributeField($builder, $attribute, $attributeOptions, $options);
        }
    }

    /**
     * Adds form type of attribute to form builder
     *
     * @param FormBuilderInterface $builder
     * @param Attribute $attribute
     * @param array $attributeOptions
     * @param array $options
     */
    protected function addAttributeField(
        FormBuilderInterface $builder,
        Attribute $attribute,
        array $attributeOptions,
        array $options
    ) {
        $attributeOptions = $this->prepareAttributeOptions($attribute, $attributeOptions, $options);

        $event = new TransitionsAttributeEvent($attribute, $attributeOptions, $options);
        $this->dispatcher->dispatch(TransitionsAttributeEvent::BEFORE_ADD, $event);
        $attributeOptions = $event->getAttributeOptions();

        $builder->add($attribute->getName(), $attributeOptions['form_type'], $attributeOptions['options']);
    }

    /**
     * Prepares options of attribute need to add corresponding form type
     *
     * @param Attribute $attribute
     * @param array $attributeOptions
     * @param array $options
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function prepareAttributeOptions(Attribute $attribute, array $attributeOptions, array $options)
    {
        /** @var Workflow $workflow */
        $workflow = $options['workflow'];

        // set default form options
        if (!isset($attributeOptions['options'])) {
            $attributeOptions['options'] = array();
        }

        // try to guess form type and form options
        $attributeOptions = $this->guessAttributeOptions($workflow, $attribute, $attributeOptions);

        // ensure that attribute has form_type
        if (empty($attributeOptions['form_type'])) {
            throw new InvalidConfigurationException(
                sprintf(
                    'Parameter "form_type" must be defined for attribute "%s" in workflow "%s".',
                    $attribute->getName(),
                    $workflow->getName()
                )
            );
        }

        // update form label
        $attributeOptions['options']['label'] = isset($attributeOptions['label'])
            ? $attributeOptions['label']
            : $attribute->getLabel();

        // update required option
        if (!array_key_exists('required', $attributeOptions['options'])) {
            $attributeOptions['options']['required'] = false;
        }

        // set disabled option
        if ($options['disable_attribute_fields']) {
            $attributeOptions['options']['disabled'] = true;
        }

        $contextAccessor = $this->contextAccessor;
        array_walk_recursive(
            $attributeOptions,
            function (&$leaf) use ($options, $contextAccessor) {
                $leaf = $contextAccessor->getValue($options['workflow_item'], $leaf);
            }
        );

        return $attributeOptions;
    }

    /**
     * Custom options:
     * - "attribute_fields"         - required, list of attributes form types options
     * - "workflow_item"            - optional, instance of WorkflowItem entity
     * - "workflow"                 - optional, instance of Workflow
     * - "disable_attribute_fields" - optional, a flag to disable all attributes fields
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('workflow_item'));

        $resolver->setDefaults(
            array(
                'workflow' => function (Options $options, $workflow) {
                    if (!$workflow) {
                        $workflowName = $options['workflow_item']->getWorkflowName();
                        $workflow = $this->workflowRegistry->getWorkflow($workflowName);
                    }
                    return $workflow;
                }
            )
        );
        $resolver->setOptional(
            array(
                'attribute_fields',
                'attribute_default_values',
                'init_actions',
                'workflow'
            )
        );

        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\WorkflowBundle\Model\WorkflowData',
                'disable_attribute_fields' => false,
                'attribute_fields' => array(),
                'attribute_default_values' => array()
            )
        );

        $resolver->setAllowedTypes(
            array(
                'workflow_item' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
                'workflow' => 'Oro\Bundle\WorkflowBundle\Model\Workflow',
                'attribute_fields' => 'array',
                'attribute_default_values' => 'array',
                'init_actions' => 'Oro\Component\Action\Action\ActionInterface',
            )
        );
    }

    /**
     * @param Workflow $workflow
     * @param Attribute $attribute
     * @param array $attributeOptions
     * @return array
     */
    protected function guessAttributeOptions(Workflow $workflow, Attribute $attribute, array $attributeOptions)
    {
        if (!empty($attributeOptions['form_type'])) {
            return $attributeOptions;
        }

        $relatedEntity = $workflow->getDefinition()->getRelatedEntity();
        $typeGuess = $this->attributeGuesser->guessClassAttributeForm($relatedEntity, $attribute);
        if (!$typeGuess) {
            return $attributeOptions;
        }

        $attributeOptions['form_type'] = $typeGuess->getType();
        $attributeOptions['options'] = array_merge_recursive($attributeOptions['options'], $typeGuess->getOptions());

        return $attributeOptions;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
