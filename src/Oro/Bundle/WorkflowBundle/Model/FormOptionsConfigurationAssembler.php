<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Exception\AssemblerException;
use Symfony\Component\Form\FormRegistryInterface;

class FormOptionsConfigurationAssembler
{
    /** @var FormRegistryInterface */
    protected $formRegistry;

    /** @var FormHandlerRegistry */
    protected $formHandlerRegistry;

    /** @var FormTemplateDataProviderRegistry */
    protected $templateDataProviderRegistry;

    /**
     * @param FormRegistryInterface $formRegistry
     * @param FormHandlerRegistry $formHandlerRegistry
     * @param FormTemplateDataProviderRegistry $templateDataProviderRegistry
     */
    public function __construct(
        FormRegistryInterface $formRegistry,
        FormHandlerRegistry $formHandlerRegistry,
        FormTemplateDataProviderRegistry $templateDataProviderRegistry
    ) {
        $this->formRegistry = $formRegistry;
        $this->formHandlerRegistry = $formHandlerRegistry;
        $this->templateDataProviderRegistry = $templateDataProviderRegistry;
    }

    /**
     * @param array $transitionConfiguration
     *
     * @throws AssemblerException
     */
    public function assemble(array $transitionConfiguration)
    {
        if (!class_exists($transitionConfiguration['form_type'], true)) {
            throw new AssemblerException(
                sprintf('Form type should be FQCN or class not found got "%s"', $transitionConfiguration['form_type'])
            );
        }

        if (!$this->formRegistry->hasType($transitionConfiguration['form_type'])) {
            throw new AssemblerException(
                sprintf('Unable to resolve form type "%s"', $transitionConfiguration['form_type'])
            );
        }

        $formOptions = $transitionConfiguration['form_options'];
        $formConfiguration = $formOptions[WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION];

        if (!$this->formHandlerRegistry->has($formConfiguration['handler'])) {
            throw new AssemblerException(
                sprintf('Unable to resolve form handler with alias "%s"', $formConfiguration['handler'])
            );
        }

        if (!$this->templateDataProviderRegistry->has($formConfiguration['data_provider'])) {
            throw new AssemblerException(
                sprintf('Unable to resolve form data provider with alias "%s"', $formConfiguration['data_provider'])
            );
        }
    }
}
