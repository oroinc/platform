<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Swift_Mailer;
use Swift_SmtpTransport;
use Swift_Transport;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

class SmtpSettingsChecker
{
    /**
     * @param SmtpSettings $smtpSettings
     *
     * @return bool|string
     */
    public function checkSmtpSettingsConnection(SmtpSettings $smtpSettings)
    {
        $error = false;

        try {
            $transport = $this->createTransportToCheck($smtpSettings);
            $mailer = $this->createMailerToCheck($transport);
            $mailer->getTransport()->start();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return $error;
    }

    protected function createTransportToCheck(SmtpSettings $smtpSettings)
    {
        $transport = Swift_SmtpTransport::newInstance(
            $smtpSettings->getHost(),
            $smtpSettings->getPort(),
            $smtpSettings->getEncryption()
        );

        $transport->setUsername($smtpSettings->getUsername());
        $transport->setPassword($smtpSettings->getPassword());
    }

    protected function createMailerToCheck(Swift_Transport $transport)
    {
        return Swift_Mailer::newInstance($transport);
    }
}
