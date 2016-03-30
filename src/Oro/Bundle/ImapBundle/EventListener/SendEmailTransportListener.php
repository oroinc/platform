<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class SendEmailTransportListener
{
    /**
     * @var Mcrypt
     */
    protected $mcrypt;
    
    /**
     * @var ImapEmailGoogleOauth2Manager
     */
    protected $imapEmailGoogleOauth2Manager;

    /**
     * @param Mcrypt $mcrypt
     * @param ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager
     */
    public function __construct(
        Mcrypt $mcrypt,
        ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager
    ) {
        $this->mcrypt = $mcrypt;
        $this->imapEmailGoogleOauth2Manager = $imapEmailGoogleOauth2Manager;
    }

    /**
     * Set smtp configuration from user imap settings
     *
     * @param SendEmailTransport $event
     */
    public function setSmtpTransport(SendEmailTransport $event)
    {
        $emailOrigin = $event->getEmailOrigin();
        if ($emailOrigin instanceof UserEmailOrigin) {
            $username = $emailOrigin->getUser();
            $password = $this->mcrypt->decryptData($emailOrigin->getPassword());
            $host = $emailOrigin->getSmtpHost();
            $port = $emailOrigin->getSmtpPort();
            $security = $emailOrigin->getSmtpEncryption();

            $transport = $event->getTransport();
            if ($transport instanceof \Swift_SmtpTransport
                || $transport instanceof \Swift_Transport_EsmtpTransport
            ) {
                $transport->setHost($host);
                $transport->setPort($port);
                $transport->setEncryption($security);
            } else {
                $transport = \Swift_SmtpTransport::newInstance($host, $port, $security);
            }

            $transport->setUsername($username);
            $transport->setPassword($password);

            $accessToken = $this->imapEmailGoogleOauth2Manager->getAccessTokenWithCheckingExpiration($emailOrigin);
            if ($accessToken !== null) {
                $transport->setAuthMode('XOAUTH2');
                $transport->setPassword($accessToken);
            }
            $event->setTransport($transport);
        }
    }
}
