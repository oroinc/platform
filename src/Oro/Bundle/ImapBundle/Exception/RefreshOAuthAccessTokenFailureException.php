<?php

namespace Oro\Bundle\ImapBundle\Exception;

use Oro\Bundle\EmailBundle\Exception\DisableOriginSyncExceptionInterface;

/**
 * This exception is thrown when receiving the access token by the refresh token failed.
 */
class RefreshOAuthAccessTokenFailureException extends \RuntimeException implements DisableOriginSyncExceptionInterface
{
    private string $reason;
    private string $refreshToken;

    public function __construct(string $reason, string $refreshToken)
    {
        $message = 'Cannot refresh OAuth access token.';
        if ($reason) {
            $message .= sprintf(' Reason: %s.', $reason);
        }
        $message .= sprintf(' Refresh Token: %s.', $refreshToken);
        parent::__construct($message);

        $this->reason = $reason;
        $this->refreshToken = $refreshToken;
    }

    /**
     * Gets the failure reason.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Gets the refresh token.
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }
}
