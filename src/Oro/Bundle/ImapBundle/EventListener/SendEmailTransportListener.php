<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
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
     * @var OAuth2ManagerRegistry
     */
    protected $oauthManagerRegistry;

    /**
     * @param SymmetricCrypterInterface $crypter
     * @param OAuth2ManagerRegistry $oauthManagerRegistry
     */
    public function __construct(
        SymmetricCrypterInterface $crypter,
        OAuth2ManagerRegistry $oauthManagerRegistry
    ) {
        $this->crypter = $crypter;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
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
                $transport = new \Swift_SmtpTransport($host, $port, $security);
            }

            $transport->setUsername($username);
            $manager = $this->oauthManagerRegistry->getManager($emailOrigin->getAccountType());
            $accessToken = $manager->getAccessTokenWithCheckingExpiration($emailOrigin);
            if ($accessToken !== null) {
                $transport->setAuthMode($manager->getAuthMode());
                $transport->setPassword($accessToken);
            } else {
                $transport->setPassword($password);
            }

            $event->setTransport($transport);
        }
    }
}
