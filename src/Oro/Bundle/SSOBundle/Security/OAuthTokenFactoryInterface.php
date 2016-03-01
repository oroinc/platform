<?php

namespace Oro\Bundle\SSOBundle\Security;

interface OAuthTokenFactoryInterface
{
    /**
     * @param string|array $accessToken
     * @param array $roles
     * @return OAuthToken
     */
    public function create($accessToken, array $roles = []);
}
