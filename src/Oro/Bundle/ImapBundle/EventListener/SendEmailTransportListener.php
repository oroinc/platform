<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Configures SMTP transport based on user IMAP settings.
 */
class SendEmailTransportListener
{
    /** @var SymmetricCrypterInterface */
    private $crypter;

    /** @var OAuthManagerRegistry */
    private $oauthManagerRegistry;

    public function __construct(
        SymmetricCrypterInterface $crypter,
        OAuthManagerRegistry $oauthManagerRegistry
    ) {
        $this->crypter = $crypter;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * Set smtp configuration from user imap settings
     */
    public function setSmtpTransport(SendEmailTransport $event)
    {
        $emailOrigin = $event->getEmailOrigin();
        if (!$emailOrigin instanceof UserEmailOrigin) {
            return;
        }

        $transport = $event->getTransport();
        if ($transport instanceof \Swift_Transport_EsmtpTransport) {
            $transport->setHost($emailOrigin->getSmtpHost());
            $transport->setPort($emailOrigin->getSmtpPort());
            $transport->setEncryption($emailOrigin->getSmtpEncryption());
        } else {
            $transport = new \Swift_SmtpTransport(
                $emailOrigin->getSmtpHost(),
                $emailOrigin->getSmtpPort(),
                $emailOrigin->getSmtpEncryption()
            );
            $event->setTransport($transport);
        }

        $transport->setUsername($emailOrigin->getUser());
        $manager = $this->oauthManagerRegistry->hasManager($emailOrigin->getAccountType())
            ? $this->oauthManagerRegistry->getManager($emailOrigin->getAccountType())
            : null;
        if (null !== $manager) {
            $accessToken = $manager->getAccessTokenWithCheckingExpiration($emailOrigin);
            if (null !== $accessToken) {
                $transport->setAuthMode($manager->getAuthMode());
                $transport->setPassword($accessToken);
            } else {
                $transport->setPassword($this->crypter->decryptData($emailOrigin->getPassword()));
            }
        } else {
            $transport->setPassword($this->crypter->decryptData($emailOrigin->getPassword()));
        }
    }
}
