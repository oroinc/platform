<?php

namespace Oro\Bundle\TranslationBundle\Api;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the "current" predefined language code as a language of the current request.
 */
class RequestPredefinedLanguageCodeResolver implements PredefinedLanguageCodeResolverInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return <<<MARKDOWN
**current** for a language of the current request.
MARKDOWN;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';
    }
}
