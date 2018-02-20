<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Extension\CustomizeFormDataExtension;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Builds the form builder based on the entity metadata and configuration
 * and sets it to the Context.
 */
class BuildFormBuilder implements ProcessorInterface
{
    /** @var FormHelper */
    protected $formHelper;

    /**
     * @param FormHelper $formHelper
     */
    public function __construct(FormHelper $formHelper)
    {
        $this->formHelper = $formHelper;
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

        $formBuilder = $this->formHelper->createFormBuilder(
            $formType,
            $context->getResult(),
            $this->getFormOptions($context, $config),
            $config->getFormEventSubscribers()
        );

        if ('form' === $formType) {
            $this->formHelper->addFormFields($formBuilder, $context->getMetadata(), $config);
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
        $options = $config->getFormOptions();
        if (null === $options) {
            $options = [];
        }
        if (!array_key_exists('data_class', $options)) {
            $options['data_class'] = $context->getClassName();
        }
        $options[CustomizeFormDataExtension::API_CONTEXT] = $context;

        return $options;
    }
}
