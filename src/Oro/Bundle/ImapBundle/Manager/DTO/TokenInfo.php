<?php

namespace Oro\Bundle\ImapBundle\Manager\DTO;

/**
 * Value object for OAuth token data
 */
class TokenInfo
{
    public const KEY_ACCESS_TOKEN = 'access_token';
    public const KEY_REFRESH_TOKEN = 'refresh_token';
    public const KEY_EXPIRES_IN = 'expires_in';

    /** @var string|null */
    private $accessToken;

    /** @var string|null */
    private $refreshToken;

    /** @var int|null */
    private $expiresIn;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->accessToken = array_key_exists(self::KEY_ACCESS_TOKEN, $data)
            ? $data[self::KEY_ACCESS_TOKEN] : null;
        $this->refreshToken = array_key_exists(self::KEY_REFRESH_TOKEN, $data)
            ? $data[self::KEY_REFRESH_TOKEN] : null;
        $this->expiresIn = array_key_exists(self::KEY_EXPIRES_IN, $data)
            ? $data[self::KEY_EXPIRES_IN] : null;
    }

    /**
     * Returns access_token
     *
     * @return null|string
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Returns refresh_token
     *
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Returns expires_in
     *
     * @return int|null
     */
    public function getExpiresIn(): ?int
    {
        return (null === $this->expiresIn)
            ? null
            : (int)$this->expiresIn;
    }


    /**
     * Returns array representation of the object
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::KEY_ACCESS_TOKEN => $this->getAccessToken(),
            self::KEY_REFRESH_TOKEN => $this->getRefreshToken(),
            self::KEY_EXPIRES_IN => $this->getExpiresIn()
        ];
    }
}
