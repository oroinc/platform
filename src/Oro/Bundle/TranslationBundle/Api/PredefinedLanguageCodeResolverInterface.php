<?php

namespace Oro\Bundle\TranslationBundle\Api;

/**
 * The interface for classes that can resolve different kind of predefined language codes used in API.
 */
interface PredefinedLanguageCodeResolverInterface
{
    /**
     * Gets the description of a predefined language code that can be resolved by this resolver.
     * This description is used in auto-generated documentation, including API sandbox.
     * The Markdown markup language can be used in the description.
     */
    public function getDescription(): string;

    /**
     * Gets a language code corresponds to a predefined value this resolvers is responsible for.
     */
    public function resolve(): string;
}
