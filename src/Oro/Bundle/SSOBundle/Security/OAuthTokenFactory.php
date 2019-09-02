<?php

namespace Oro\Bundle\SSOBundle\Security;

/**
 * The factory to create OAuthToken.
 */
class OAuthTokenFactory implements OAuthTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($accessToken, array $roles = [])
    {
        return new OAuthToken($accessToken, $roles);
    }
}
