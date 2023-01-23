<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;

/**
 * Resolves form template data provider to FormTemplateDataProviderInterface
 */
class FormTemplateDataProviderResolver
{
    private FormTemplateDataProviderRegistry $formTemplateDataProviderRegistry;

    public function __construct(FormTemplateDataProviderRegistry $formTemplateDataProviderRegistry)
    {
        $this->formTemplateDataProviderRegistry = $formTemplateDataProviderRegistry;
    }

    public function resolve(
        FormTemplateDataProviderInterface|string|callable|null $resultProvider = null
    ): FormTemplateDataProviderInterface {
        $resultProvider = $resultProvider ?: FormTemplateDataProviderRegistry::DEFAULT_PROVIDER_NAME;

        if ($resultProvider instanceof FormTemplateDataProviderInterface) {
            return $resultProvider;
        }

        if (is_callable($resultProvider)) {
            return new CallbackFormTemplateDataProvider($resultProvider);
        }

        return $this->formTemplateDataProviderRegistry->get($resultProvider);
    }
}
