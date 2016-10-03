<?php

namespace Oro\Bundle\ImapBundle\Exception;

class RefreshOAuthAccessTokenFailureException extends \RuntimeException
{
    /** @var string */
    private $reason;

    /** @var string */
    private $refreshToken;

    /**
     * @param string $reason
     * @param string $refreshToken
     */
    public function __construct($reason, $refreshToken)
    {
        $message = 'Cannot refresh OAuth2 access token.';
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
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Gets the refresh token.
     *
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }
}
