<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class DirectMailerMock extends DirectMailer
{
    /**
     * {@inheritdoc}
     */
    public function prepareEmailOriginSmtpTransport($emailOrigin)
    {
        if ($emailOrigin instanceof UserEmailOrigin &&
            $emailOrigin->getSmtpHost() === 'smtp.example.org' &&
            (string) $emailOrigin->getSmtpPort() === '2525'
        ) {
            $this->smtpTransport = new SmtpTransportMock(
                $emailOrigin->getSmtpHost(),
                (string) $emailOrigin->getSmtpPort(),
                $emailOrigin->getSmtpEncryption(),
                $emailOrigin->getUser(),
                $this->container->get('oro_security.encoder.default')->decryptData($emailOrigin->getPassword())
            );
        } else {
            parent::prepareEmailOriginSmtpTransport($emailOrigin);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function afterPrepareSmtpTransport(SmtpSettings $smtpSettings = null)
    {
        if ($smtpSettings instanceof SmtpSettings &&
            $smtpSettings->getHost() === 'smtp.example.org' &&
            (string) $smtpSettings->getPort() === '2525'
        ) {
            $this->smtpTransport = new SmtpTransportMock(
                $smtpSettings->getHost(),
                (string) $smtpSettings->getPort(),
                $smtpSettings->getEncryption(),
                $smtpSettings->getUsername(),
                $smtpSettings->getPassword()
            );
        } else {
            parent::afterPrepareSmtpTransport($smtpSettings);
        }
    }
}
