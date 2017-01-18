<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Extension\CustomizeFormDataExtension;
use Oro\Bundle\ApiBundle\Form\FormUtil;
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

        $context->setFormBuilder($this->getFormBuilder($context));
    }

    /**
     * @param FormContext $context
     *
     * @return FormBuilderInterface
     */
    protected function getFormBuilder(FormContext $context)
    {
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

        return $formBuilder;
    }

    /**
     * @param FormContext            $context
     * @param EntityDefinitionConfig $config
     *
     * @return array
     */
    protected function getFormOptions(FormContext $context, EntityDefinitionConfig $config)
    {
        $options = array_merge(FormUtil::getFormDefaultOptions(), $config->getFormOptions() ?: []);
        if (!array_key_exists('data_class', $options)) {
            $options['data_class'] = $context->getClassName();
        }
        $options[CustomizeFormDataExtension::API_CONTEXT] = $context;

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
                FormUtil::getFormFieldOptions($field, $fieldConfig)
            );
        }
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            $fieldConfig = $config->getField($name);

            $formBuilder->add(
                $name,
                $fieldConfig->getFormType(),
                FormUtil::getFormFieldOptions($association, $fieldConfig)
            );
        }
    }
}
