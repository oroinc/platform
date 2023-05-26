<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\TranslationBundle\Api\PredefinedLanguageCodeDocumentationProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds documentation about predefined language codes to API resource documentation.
 */
class AddPredefinedLanguageCodeDocumentation implements ProcessorInterface
{
    private PredefinedLanguageCodeDocumentationProvider $documentationProvider;

    public function __construct(PredefinedLanguageCodeDocumentationProvider $documentationProvider)
    {
        $this->documentationProvider = $documentationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $predefinedLanguageCodeDocumentation = $this->documentationProvider->getDocumentation();
        if (!$predefinedLanguageCodeDocumentation) {
            return;
        }

        $definition = $context->getResult();
        $definition->setDocumentation($definition->getDocumentation() . $predefinedLanguageCodeDocumentation);
    }
}
