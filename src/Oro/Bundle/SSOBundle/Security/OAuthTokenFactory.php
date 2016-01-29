<?php

namespace Oro\Bundle\SSOBundle\Security;

class OAuthTokenFactory implements OAuthTokenFactoryInterface
{
    /**
     * @param string|array $accessToken
     * @param array $roles
     * @return OAuthToken
     */
    public function create($accessToken, array $roles = [])
    {
        return new OAuthToken($accessToken, $roles);
    }
}
