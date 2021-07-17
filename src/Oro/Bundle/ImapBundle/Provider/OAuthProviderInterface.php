<?php

namespace Oro\Bundle\ImapBundle\Provider;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Oro\Bundle\ImapBundle\Exception\OAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;

/**
 * Provides set of methods to handle OAuth authentication.
 */
interface OAuthProviderInterface
{
    /**
     * Gets the URL to where authorization requests should be sent.
     */
    public function getAuthorizationUrl(): string;

    /**
     * Gets the URL to where authentication responses should be sent.
     */
    public function getRedirectUrl(): string;

    /**
     * Requests the access token by the authorization code.
     *
     * @param string        $code
     * @param string[]|null $scopes
     *
     * @return OAuthAccessTokenData
     *
     * @throws OAuthAccessTokenFailureException if receiving the access token failed
     */
    public function getAccessTokenByAuthCode(string $code, array $scopes = null): OAuthAccessTokenData;

    /**
     * Requests the access token by the refresh token.
     *
     * @param string        $refreshToken
     * @param string[]|null $scopes
     *
     * @return OAuthAccessTokenData
     *
     * @throws RefreshOAuthAccessTokenFailureException if receiving the access token failed
     */
    public function getAccessTokenByRefreshToken(string $refreshToken, array $scopes = null): OAuthAccessTokenData;

    /**
     * Requests the information about a user issued the given assess token.
     */
    public function getUserInfo(string $accessToken): UserResponseInterface;
}
