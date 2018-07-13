<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer;

use Swift_Events_EventListener;
use Swift_Mime_Message;

class SmtpTransportMock implements \Swift_Transport
{
    /** @var string */
    private $host;

    /** @var string */
    private $port;

    /** @var string */
    private $encryption;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var bool */
    private $started = false;

    /**
     * @param string $host
     * @param string $port
     * @param string $encryption
     * @param string $username
     * @param string $password
     */
    public function __construct(string $host, string $port, string $encryption, string $username, string $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if ($this->host === 'smtp.example.org' &&
            $this->port === '2525' &&
            $this->encryption === 'ssl' &&
            $this->username === 'test_user' &&
            $this->password === 'test_password'
        ) {
            $this->started = true;
            return;
        }

        throw new \Swift_TransportException('Could not establish connection');
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->started = false;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
    }
}
