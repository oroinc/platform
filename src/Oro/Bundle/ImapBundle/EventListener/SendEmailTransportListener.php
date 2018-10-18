<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Configure SMTP transport based on user IMAP settings
 */
class SendEmailTransportListener
{
    /**
     * @var SymmetricCrypterInterface
     */
    protected $crypter;
    
    /**
     * @var ImapEmailGoogleOauth2Manager
     */
    protected $imapEmailGoogleOauth2Manager;

    /**
     * @param SymmetricCrypterInterface $crypter
     * @param ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager
     */
    public function __construct(
        SymmetricCrypterInterface $crypter,
        ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager
    ) {
        $this->crypter = $crypter;
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
            $password = $this->crypter->decryptData($emailOrigin->getPassword());
            $host = $emailOrigin->getSmtpHost();
            $port = $emailOrigin->getSmtpPort();
            $security = $emailOrigin->getSmtpEncryption();

            $transport = $event->getTransport();
            if ($transport instanceof \Swift_Transport_EsmtpTransport) {
                $transport->setHost($host);
                $transport->setPort($port);
                $transport->setEncryption($security);
            } else {
                $transport = \Swift_SmtpTransport::newInstance($host, $port, $security);
            }

            $transport->setUsername($username);

            $accessToken = $this->imapEmailGoogleOauth2Manager->getAccessTokenWithCheckingExpiration($emailOrigin);
            if ($accessToken !== null) {
                $transport->setAuthMode('XOAUTH2');
                $transport->setPassword($accessToken);
            } else {
                $transport->setPassword($password);
            }

            $event->setTransport($transport);
        }
    }
}
