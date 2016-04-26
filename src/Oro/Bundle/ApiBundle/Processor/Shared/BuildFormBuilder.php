<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * Builds the form builder based on the entity metadata and configuration
 * and sets it to the Context.
 */
class BuildFormBuilder implements ProcessorInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        if ($context->hasFormBuilder()) {
            // the form builder is already built
            return;
        }
        if ($context->hasForm()) {
            // the form is already built
            return;
        }

        $config = $context->getConfig();
        $formType = $config->getFormType() ?: 'form';

        $formBuilder = $this->formFactory->createNamedBuilder(
            null,
            $formType,
            $context->getResult(),
            $this->getFormOptions($context, $config)
        );
        if ('form' === $formType) {
            $this->addFormFields($formBuilder, $context->getMetadata(), $config);
        }
        $context->setFormBuilder($formBuilder);
    }

    /**
     * @param FormContext            $context
     * @param EntityDefinitionConfig $config
     *
     * @return array
     */
    protected function getFormOptions(FormContext $context, EntityDefinitionConfig $config)
    {
        $options = $config->getFormOptions();
        if (null === $options) {
            $options = [];
        }
        if (!array_key_exists('data_class', $options)) {
            $options['data_class'] = $context->getClassName();
        }
        if (!array_key_exists('validation_groups', $options)) {
            $options['validation_groups'] = ['Default', 'api'];
        }
        if (!array_key_exists('extra_fields_message', $options)) {
            $options['extra_fields_message'] = 'This form should not contain extra fields: "{{ extra_fields }}"';
        }

        return $options;
    }

    /**
     * @param FormBuilderInterface   $formBuilder
     * @param EntityMetadata         $metadata
     * @param EntityDefinitionConfig $config
     */
    protected function addFormFields(
        FormBuilderInterface $formBuilder,
        EntityMetadata $metadata,
        EntityDefinitionConfig $config
    ) {
        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            $fieldConfig = $config->getField($name);
            $formBuilder->add(
                $name,
                $fieldConfig->getFormType(),
                $this->getFormFieldOptions($fieldConfig)
            );
        }
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            $fieldConfig = $config->getField($name);

            $formBuilder->add(
                $name,
                $fieldConfig->getFormType(),
                $this->getFormFieldOptions($fieldConfig)
            );
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $fieldConfig
     *
     * @return array
     */
    protected function getFormFieldOptions(EntityDefinitionFieldConfig $fieldConfig)
    {
        $options = $fieldConfig->getFormOptions();
        if (null === $options) {
            $options = [];
        }
        $propertyPath = $fieldConfig->getPropertyPath();
        if ($propertyPath) {
            $options['property_path'] = $propertyPath;
        }

        return $options;
    }
}
