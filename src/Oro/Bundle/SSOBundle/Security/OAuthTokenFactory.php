<?php

namespace Oro\Bundle\SSOBundle\Security;

/**
 * The factory to create OAuthToken.
 */
class OAuthTokenFactory implements OAuthTokenFactoryInterface
{
    #[\Override]
    public function create($accessToken, array $roles = [])
    {
        return new OAuthToken($accessToken, $roles);
    }
}
