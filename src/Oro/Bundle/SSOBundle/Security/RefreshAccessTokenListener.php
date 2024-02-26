<?php

namespace Oro\Bundle\SSOBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Http\Firewall\AbstractRefreshAccessTokenListener;

/**
 * This file is a copy of {@see HWI\Bundle\OAuthBundle\Security\Http\Firewall\RefreshAccessTokenListener}
 *
 * Copyright (c) 2012-2019 Hardware Info - https://hardware.info
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */
class RefreshAccessTokenListener extends AbstractRefreshAccessTokenListener
{
    private OAuthAuthenticator $oAuthAuthenticator;

    public function __construct(
        OAuthAuthenticator $oAuthAuthenticator
    ) {
        $this->oAuthAuthenticator = $oAuthAuthenticator;
    }

    /**
     * @template T of OAuthToken
     *
     * @param T $token
     *
     * @return T
     */
    protected function refreshToken(OAuthToken $token): OAuthToken
    {
        return $this->oAuthAuthenticator->refreshToken($token);
    }
}
