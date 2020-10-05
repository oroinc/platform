<?php

namespace Oro\Bundle\ImapBundle\Manager;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Manager\DTO\TokenInfo;

/**
 * Provides set of methods to handle OAuth authentication for
 * IMAP/SMTP accounts. Each implementation of this interface should be
 * tagged with oro_imap.oauth2_manager
 */
interface Oauth2ManagerInterface
{
    /**
     * Returns unique type name of the manager implementation
     *
     * @return string
     */
    public function getType(): string;


    /**
     * Returns form type class name for check connection widget
     *
     * @return string
     */
    public function getConnectionFormTypeClass(): string;

    /**
     * Sets user email origin defaults
     *
     * @param UserEmailOrigin $origin
     */
    public function setOriginDefaults(UserEmailOrigin $origin): void;

    /**
     * Tries returning access token checking it's expiration
     *
     * @param UserEmailOrigin $origin
     * @return null|string
     */
    public function getAccessTokenWithCheckingExpiration(UserEmailOrigin $origin);

    /**
     * Checks if origin token is expired
     *
     * @param UserEmailOrigin $origin
     * @return bool
     */
    public function isAccessTokenExpired(UserEmailOrigin $origin);

    /**
     * Provides access token data array by Auth code
     *
     * @param string $code
     * @return array
     *
     * @deprecated Since 4.2.0
     * Please use \Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface::getAccessTokenDataByAuthCode()
     */
    public function getAccessTokenByAuthCode(string $code);

    /**
     * Refreshes access token for given User Email Origin
     *
     * @param UserEmailOrigin $origin
     *
     * @throws RefreshOAuthAccessTokenFailureException
     */
    public function refreshAccessToken(UserEmailOrigin $origin);

    /**
     * Provides access token data object by auth code
     *
     * @param string $code
     * @return TokenInfo
     */
    public function getAccessTokenDataByAuthCode(string $code): TokenInfo;

    /**
     * Provides access token data object by auth code
     *
     * @param string $refreshToken
     * @return TokenInfo
     */
    public function getAccessTokenDataByRefreshToken(string $refreshToken): TokenInfo;

    /**
     * Returns user response instance of \HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface
     *
     * @param TokenInfo $tokenInfo
     * @return UserResponseInterface
     */
    public function getUserInfo(TokenInfo $tokenInfo);

    /**
     * Returns authentication mode for email transport
     *
     * @return string
     */
    public function getAuthMode(): string;

    /**
     * Returns true if OAuth is enabled for this certain type
     *
     * @return bool
     */
    public function isOAuthEnabled(): bool;
}
