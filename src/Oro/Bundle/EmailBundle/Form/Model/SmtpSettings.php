<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

class SmtpSettings
{
    const DEFAULT_HOST = '';
    const DEFAULT_PORT = null;
    const DEFAULT_ENCRYPTION = null;
    const DEFAULT_USERNAME = null;
    const DEFAULT_PASSWORD = null;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

    /**
     * The SSL type to be used to connect to the server. Can be empty string, 'ssl' or 'tls'
     *
     * @var string
     */
    protected $encryption;

    /**
     * @var string|null
     */
    protected $username;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string|null
     */
    protected $password;

    /**
     * SmtpSettings constructor.
     *
     * @param string      $host
     * @param integer     $port
     * @param string      $encryption
     * @param string|null $username
     * @param string|null $password
     */
    public function __construct(
        $host = self::DEFAULT_HOST,
        $port = self::DEFAULT_PORT,
        $encryption = self::DEFAULT_ENCRYPTION,
        $username = self::DEFAULT_USERNAME,
        $password = self::DEFAULT_PASSWORD
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getHost();
    }

    /**
     * Checks host, port and encryption to not have default values.
     * Host and port cannot be empty.
     * Username and password can be empty in certain setups and still have a valid connection (eg. localhost).
     *
     * @return bool
     */
    public function isEligible()
    {
        return (self::DEFAULT_HOST !== $this->host
            && self::DEFAULT_PORT !== $this->port
            && self::DEFAULT_ENCRYPTION !== $this->encryption
            && !empty($this->host)
            && is_numeric($this->port));
    }

    /**
     * Gets the host name of the server
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the host name of the server
     *
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     *
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEncryption()
    {
        return $this->encryption;
    }

    /**
     * @param string|null $encryption
     *
     * @return $this
     */
    public function setEncryption($encryption)
    {
        $this->encryption = $encryption;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }
}
