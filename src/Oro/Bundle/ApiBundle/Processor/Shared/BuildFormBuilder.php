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
 * Builds the form builder based on the entity metadata and configuration.
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

        $formBuilder = $this->formFactory->createBuilder(
            'form',
            null,
            $this->getFormOptions($context)
        );
        $this->addFormFields($formBuilder, $context->getMetadata(), $context->getConfig());
        $context->setFormBuilder($formBuilder);
    }

    /**
     * @param FormContext $context
     *
     * @return array
     */
    protected function getFormOptions(FormContext $context)
    {
        return [
            'data_class'           => $context->getClassName(),
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ];
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
