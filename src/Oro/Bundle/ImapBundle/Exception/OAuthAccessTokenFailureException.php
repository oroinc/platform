<?php

namespace Oro\Bundle\ImapBundle\Exception;

/**
 * This exception is thrown when receiving the access token failed.
 */
class OAuthAccessTokenFailureException extends \RuntimeException
{
    private string $reason;
    private string $authorizationCode;

    public function __construct(string $reason, string $authorizationCode)
    {
        $message = 'Cannot get OAuth access token.';
        if ($reason) {
            $message .= sprintf(' Reason: %s.', $reason);
        }
        $message .= sprintf(' Authorization Code: %s.', $authorizationCode);
        parent::__construct($message);

        $this->reason = $reason;
        $this->authorizationCode = $authorizationCode;
    }

    /**
     * Gets the failure reason.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Gets the authorization code.
     */
    public function getAuthorizationCode(): string
    {
        return $this->authorizationCode;
    }
}
