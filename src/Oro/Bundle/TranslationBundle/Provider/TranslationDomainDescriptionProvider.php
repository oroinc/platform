<?php

namespace Oro\Bundle\TranslationBundle\Provider;

/**
 * The base implementation of a service to get human-readable description of translation domains.
 */
class TranslationDomainDescriptionProvider implements TranslationDomainDescriptionProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTranslationDomainDescription(string $domain): ?string
    {
        switch ($domain) {
            case 'messages':
                return 'Default translation domain.';
            case 'jsmessages':
                return 'Translation messages used on the client side (JavaScript).';
            case 'validators':
                return 'Data validation constraint violation messages.';
            case 'security':
                return 'Security error messages.';
            case 'entities':
                return 'Entity related dictionaries (e.g. country names, region names, address types, etc.)';
            case 'workflows':
                return 'Messages related to workflows.';
        }

        return null;
    }
}
