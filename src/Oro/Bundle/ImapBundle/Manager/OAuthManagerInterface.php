<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;

/**
 * Provides set of methods to handle OAuth authentication for IMAP/SMTP accounts.
 */
interface OAuthManagerInterface
{
    /**
     * Gets a string that uniquely identifies a type the manager.
     */
    public function getType(): string;

    /**
     * Checks if OAuth authentication is enabled.
     */
    public function isOAuthEnabled(): bool;

    /**
     * Gets the authentication mode for the email transport.
     */
    public function getAuthMode(): string;

    /**
     * Gets the class name of the form type for the check connection widget.
     */
    public function getConnectionFormTypeClass(): string;

    /**
     * Sets default attributes for the user email origin.
     */
    public function setOriginDefaults(UserEmailOrigin $origin): void;

    /**
     * Checks if the access token for the user email origin is expired.
     */
    public function isAccessTokenExpired(UserEmailOrigin $origin): bool;

    /**
     * Gets the access token for the user email origin.
     * When the existing access token expired, the new access token is requested by the refresh token.
     *
     * @throws RefreshOAuthAccessTokenFailureException if the user email origin does not have the refresh token
     *                                                 or receiving the access token by the refresh token failed
     */
    public function getAccessTokenWithCheckingExpiration(UserEmailOrigin $origin): ?string;

    /**
     * Refreshes the access token for the user email origin by its the refresh token.
     *
     * @throws RefreshOAuthAccessTokenFailureException if the user email origin does not have the refresh token
     *                                                 or receiving the access token by the refresh token failed
     */
    public function refreshAccessToken(UserEmailOrigin $origin): void;
}
