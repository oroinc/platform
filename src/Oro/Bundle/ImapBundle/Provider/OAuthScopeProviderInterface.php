<?php

namespace Oro\Bundle\ImapBundle\Provider;

/**
 * Represents a service to get scopes to be requested for different types of OAuth access tokens.
 */
interface OAuthScopeProviderInterface
{
    /**
     * Gets the list of scopes to be requested for the given type of OAuth access token.
     *
     * @param string $tokenType
     *
     * @return string[]|null
     */
    public function getAccessTokenScopes(string $tokenType): ?array;
}
