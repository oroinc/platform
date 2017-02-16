<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\Form\FormRegistryInterface;

use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Exception\AssemblerException;

class PageFormConfigurationAssembler
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
     * @param Transition $transition
     *
     * @throws AssemblerException
     */
    public function assemble(array $transitionConfiguration, Transition $transition)
    {
        if (!$this->formRegistry->hasType($transitionConfiguration['form_type'])) {
            throw new AssemblerException(
                sprintf('Unable to resolve form type "%s"', $transitionConfiguration['form_type'])
            );
        }

        $pageFormConfiguration = $transitionConfiguration[WorkflowConfiguration::NODE_PAGE_FORM_CONFIGURATION];

        if (!$this->formHandlerRegistry->has($pageFormConfiguration['handler'])) {
            throw new AssemblerException(
                sprintf('Unable to resolve form handler with alias "%s"', $pageFormConfiguration['handler'])
            );
        }

        if (!$this->templateDataProviderRegistry->has($pageFormConfiguration['data_provider'])) {
            throw new AssemblerException(
                sprintf('Unable to resolve form data provider with alias "%s"', $pageFormConfiguration['data_provider'])
            );
        }

        $transition->setPageFormHandler($pageFormConfiguration['handler'])
            ->setPageFormDataAttribute($pageFormConfiguration['data_attribute'])
            ->setPageFormTemplate($pageFormConfiguration['template'])
            ->setPageFormDataProvider($pageFormConfiguration['data_provider'])
            ->setHasPageFormConfiguration(true);
    }
}
