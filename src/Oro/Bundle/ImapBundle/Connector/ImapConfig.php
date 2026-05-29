<?php

namespace Oro\Bundle\ImapBundle\Connector;

/**
 * Encapsulates IMAP server connection configuration parameters.
 */
class ImapConfig
{
    private ?int $connectionTimeout = null;

    public function __construct(
        private ?string $host = null,
        private ?int $port = null,
        private ?string $ssl = null,
        private ?string $user = null,
        private ?string $password = null,
        private ?string $accessToken = null
    ) {
    }

    /**
     * Gets the host name of IMAP server.
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Sets the host name of IMAP server.
     */
    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    /**
     * Gets the port of IMAP server.
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * Sets the port of IMAP server.
     */
    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    /**
     * Gets the encryption type to be used to connect to IMAP server.
     * Can be null, empty string, "ssl" or "tls".
     */
    public function getSsl(): ?string
    {
        return $this->ssl;
    }

    /**
     * Sets the encryption type to be used to connect to IMAP server.
     * Can be null, empty string, "ssl" or "tls".
     */
    public function setSsl(?string $ssl): void
    {
        $this->ssl = $ssl;
    }

    /**
     * Gets the username.
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * Sets the username.
     */
    public function setUser(?string $user): void
    {
        $this->user = $user;
    }

    /**
     * Gets the user password.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Sets the user password.
     */
    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    /**
     * Gets user OAuth2 access token.
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Sets user OAuth2 access token.
     */
    public function setAccessToken(?string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Gets the connection timeout in seconds.
     */
    public function getConnectionTimeout(): ?int
    {
        return $this->connectionTimeout;
    }

    /**
     * Sets the connection timeout in seconds.
     * To reset the timeout to the default value, pass null or 0.
     */
    public function setConnectionTimeout(?int $timeoutInSeconds): void
    {
        $this->connectionTimeout = $timeoutInSeconds;
    }
}
