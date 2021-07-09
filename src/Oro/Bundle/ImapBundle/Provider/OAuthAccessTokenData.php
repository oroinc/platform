<?php

namespace Oro\Bundle\ImapBundle\Provider;

/**
 * Represents a new OAuth access token.
 */
class OAuthAccessTokenData
{
    private string $accessToken;
    private ?string $refreshToken;
    private ?int $expiresIn;

    public function __construct(string $accessToken, ?string $refreshToken, ?int $expiresIn)
    {
        if (!$accessToken) {
            throw new \InvalidArgumentException('The access token must not be empty.');
        }
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn = $expiresIn;
    }

    /**
     * Gets the access token.
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Gets the refresh token.
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Gets the number of seconds indicates how long the access token is valid.
     */
    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }
}
