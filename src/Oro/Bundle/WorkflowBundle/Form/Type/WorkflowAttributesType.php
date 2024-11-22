<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SecurityBundle\Util\PropertyPathSecurityHelper;
use Oro\Bundle\WorkflowBundle\Event\TransitionsAttributeEvent;
use Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This is used to edit workflow attributes in workflow configurator.
 */
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
     * @var FormInitListener
     */
    protected $formInitListener;

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
     * @var PropertyPathSecurityHelper
     */
    protected $propertyPathSecurityHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    protected ?PropertyAccessorInterface $propertyAccessor = null;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param AttributeGuesser $attributeGuesser ,
     * @param DefaultValuesListener $defaultValuesListener
     * @param FormInitListener $formInitListener
     * @param RequiredAttributesListener $requiredAttributesListener
     * @param ContextAccessor $contextAccessor
     * @param EventDispatcherInterface $dispatcher
     * @param PropertyPathSecurityHelper $propertyPathSecurityHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        WorkflowRegistry $workflowRegistry,
        AttributeGuesser $attributeGuesser,
        DefaultValuesListener $defaultValuesListener,
        FormInitListener $formInitListener,
        RequiredAttributesListener $requiredAttributesListener,
        ContextAccessor $contextAccessor,
        EventDispatcherInterface $dispatcher,
        PropertyPathSecurityHelper $propertyPathSecurityHelper,
        TranslatorInterface $translator
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->attributeGuesser = $attributeGuesser;
        $this->defaultValuesListener = $defaultValuesListener;
        $this->formInitListener = $formInitListener;
        $this->requiredAttributesListener = $requiredAttributesListener;
        $this->contextAccessor = $contextAccessor;
        $this->dispatcher = $dispatcher;
        $this->propertyPathSecurityHelper = $propertyPathSecurityHelper;
        $this->translator = $translator;
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

        $builder->addEventSubscriber($this->formInitListener);

        if (!empty($options['attribute_fields'])) {
            $this->requiredAttributesListener->initialize(array_keys($options['attribute_fields']));
            $builder->addEventSubscriber($this->requiredAttributesListener);
        }
    }

    /**
     * Add attributes to form
     *
     * @throws InvalidConfigurationException When attribute is not found in given Workflow
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addAttributes(FormBuilderInterface $builder, array $options)
    {
        /** @var Workflow $workflow */
        $workflow = $options['workflow'];
        $attributes = [];
        $config = $workflow->getDefinition()->getConfiguration();
        if (isset($config['attributes'])) {
            $attributes = $workflow->getDefinition()->getConfiguration()['attributes'];
        }
        $entity = null;
        if (isset($options['data'])) {
            /** @var WorkflowData $data */
            $data = $options['data'];
            $entity = $data->get($workflow->getDefinition()->getEntityAttributeName());
        }

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
                $attributeOptions = [];
            }
            $fieldName = $attribute->getName();
            if (isset($attributes[$fieldName])) {
                $attributeConfiguration = $attributes[$fieldName];
                if (array_key_exists('property_path', $attributeConfiguration)
                    && $attributeConfiguration['property_path']
                ) {
                    $fieldName = $attributeConfiguration['property_path'];
                }
            }
            if (!$entity || $this->isEditableField($entity, $fieldName)) {
                $this->addAttributeField($builder, $attribute, $attributeOptions, $options);
            }
        }
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isEditableField($entity, $fieldName)
    {
        $propertyPath = new PropertyPath($fieldName);
        $pathElements = array_values($propertyPath->getElements());
        if ($propertyPath->getLength() >= 2) {
            array_shift($pathElements);
            $fieldName = implode('.', $pathElements);
        }

        // checking virtual attributes
        if (!$this->getPropertyAccessor()->isWritable($entity, $fieldName)) {
            return true;
        }

        return $this->propertyPathSecurityHelper->isGrantedByPropertyPath(
            $entity,
            $fieldName,
            'EDIT'
        );
    }

    /**
     * Adds form type of attribute to form builder
     */
    protected function addAttributeField(
        FormBuilderInterface $builder,
        Attribute $attribute,
        array $attributeOptions,
        array $options
    ) {
        $attributeOptions = $this->prepareAttributeOptions($attribute, $attributeOptions, $options);

        $event = new TransitionsAttributeEvent($attribute, $attributeOptions, $options);
        $this->dispatcher->dispatch($event, TransitionsAttributeEvent::BEFORE_ADD);
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
            $attributeOptions['options'] = [];
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
        $attributeOptions = $this->resolveLabel($attribute, $attributeOptions);

        // update required option
        if (!array_key_exists('required', $attributeOptions['options'])) {
            $attributeOptions['options']['required'] = false;
        }

        // set disabled option
        if ($options['disable_attribute_fields']) {
            $attributeOptions['options']['disabled'] = true;
        }

        return $this->resolveContextValue($options['workflow_item'], $attributeOptions);
    }

    /**
     * @param Attribute $attribute
     * @param array $attributeOptions
     * @return array
     */
    protected function resolveLabel(Attribute $attribute, array $attributeOptions)
    {
        if (isset($attributeOptions['options']['translation_domain'])) {
            $domain = $attributeOptions['options']['translation_domain'];
        } else {
            $domain = WorkflowTranslationHelper::TRANSLATION_DOMAIN;
        }

        if (isset($attributeOptions['label'])) {
            $attributeOptions['options']['label'] = $attributeOptions['label'];
        } elseif (isset($attributeOptions['options']['label'])) {
            if (is_array($attributeOptions['options']['label'])) {
                $attributeOptions['options']['label'] = array_shift($attributeOptions['options']['label']);
            }

            $label = $attributeOptions['options']['label'];
            $translatedLabel =  $this->translator->trans((string) $label, [], $domain);
            if ($translatedLabel === $label) {
                $attributeOptions['options']['label'] = $attribute->getLabel();
            }
        } else {
            $attributeOptions['options']['label'] = $attribute->getLabel();
        }

        if (str_starts_with($attributeOptions['options']['label'], WorkflowTemplate::KEY_PREFIX)) {
            $attributeOptions['options']['translation_domain'] = $domain;
        }

        return $attributeOptions;
    }

    /**
     * This method is used instead of `array_walk_recursive`,
     * because `array_walk_recursive` moves array pointer to the end. So `current` function returns false.
     *
     * @param $context
     * @param mixed $option
     * @return array|mixed
     */
    protected function resolveContextValue($context, $option)
    {
        if (is_array($option)) {
            return array_map(function ($value) use ($context) {
                return $this->resolveContextValue($context, $value);
            }, $option);
        }

        return $this->contextAccessor->getValue($context, $option);
    }

    /**
     * Custom options:
     * - "attribute_fields"         - required, list of attributes form types options
     * - "workflow_item"            - optional, instance of WorkflowItem entity
     * - "workflow"                 - optional, instance of Workflow
     * - "disable_attribute_fields" - optional, a flag to disable all attributes fields
     *
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['workflow_item']);

        $resolver->setDefaults(
            [
                'workflow' => function (Options $options, $workflow) {
                    if (!$workflow) {
                        $workflowName = $options['workflow_item']->getWorkflowName();
                        $workflow = $this->workflowRegistry->getWorkflow($workflowName);
                    }

                    return $workflow;
                }
            ]
        );
        $resolver->setDefined(
            [
                'attribute_fields',
                'attribute_default_values',
                'form_init',
                'workflow'
            ]
        );

        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\WorkflowBundle\Model\WorkflowData',
                'disable_attribute_fields' => false,
                'attribute_fields' => [],
                'attribute_default_values' => []
            ]
        );

        $resolver->setAllowedTypes('workflow_item', 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem');
        $resolver->setAllowedTypes('attribute_fields', 'array');
        $resolver->setAllowedTypes('attribute_default_values', 'array');
        $resolver->setAllowedTypes('form_init', 'Oro\Component\Action\Action\ActionInterface');
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
     * @return PropertyAccessorInterface
     */
    protected function getPropertyAccessor()
    {
        if ($this->propertyAccessor === null) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
