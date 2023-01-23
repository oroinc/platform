<?php

namespace Oro\Bundle\FormBundle\Provider;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Composite provider that polls inner form template data providers.
 */
class FormTemplateDataProviderComposite implements FormTemplateDataProviderInterface
{
    private FormTemplateDataProviderResolver $formTemplateDataProviderResolver;

    private array $formTemplateDataProviders = [];

    public function __construct(FormTemplateDataProviderResolver $formTemplateDataProviderResolver)
    {
        $this->formTemplateDataProviderResolver = $formTemplateDataProviderResolver;
    }

    public function addFormTemplateDataProviders(
        FormTemplateDataProviderInterface|string|callable|null $formTemplateDataProvider = null
    ): self {
        $this->formTemplateDataProviders[] = $formTemplateDataProvider;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($entity, FormInterface $form, Request $request)
    {
        $data = [[]];
        foreach ($this->formTemplateDataProviders as $formTemplateDataProvider) {
            $formTemplateDataProvider = $this->formTemplateDataProviderResolver->resolve($formTemplateDataProvider);

            $data[] = $formTemplateDataProvider->getData($entity, $form, $request);
        }

        return array_merge(...$data);
    }
}
