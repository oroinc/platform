<?php

namespace Oro\Bundle\TranslationBundle\Api;

/**
 * Builds a documentation for predefined language codes used in API.
 */
class PredefinedLanguageCodeDocumentationProvider
{
    private PredefinedLanguageCodeResolverRegistry $predefinedLanguageCodeResolverRegistry;

    public function __construct(PredefinedLanguageCodeResolverRegistry $predefinedLanguageCodeResolverRegistry)
    {
        $this->predefinedLanguageCodeResolverRegistry = $predefinedLanguageCodeResolverRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentation(): ?string
    {
        $descriptions = $this->predefinedLanguageCodeResolverRegistry->getDescriptions();
        if (empty($descriptions)) {
            return null;
        }

        $items = [];
        foreach ($descriptions as $description) {
            $items[] = '- ' . $description;
        }

        return sprintf($this->getTemplate(), implode("\n", $items));
    }

    private function getTemplate(): string
    {
        return <<<MARKDOWN
**Note**: The following predefined language codes are supported:

%s
MARKDOWN;
    }
}
