<?php

namespace Oro\Bundle\TranslationBundle\Provider;

/**
 * Represents a service to get human-readable description of translation domains.
 */
interface TranslationDomainDescriptionProviderInterface
{
    public function getTranslationDomainDescription(string $domain): ?string;
}
