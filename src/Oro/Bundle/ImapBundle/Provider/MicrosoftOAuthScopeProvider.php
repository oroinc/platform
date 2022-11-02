<?php

namespace Oro\Bundle\ImapBundle\Provider;

/**
 * Provides scopes for the OAuth access token request for Microsoft IMAP/SMTP synchronization.
 * It is default scopes.
 */
class MicrosoftOAuthScopeProvider implements OAuthScopeProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAccessTokenScopes(string $tokenType): ?array
    {
        return [
            'offline_access',
            'https://outlook.office.com/IMAP.AccessAsUser.All',
            'https://outlook.office.com/POP.AccessAsUser.All',
            'https://outlook.office.com/SMTP.Send'
        ];
    }
}
