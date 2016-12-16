<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

class SmtpSettingsChecker
{
    /**
     * @param SmtpSettings $smtpSettings
     *
     * @return bool|string
     */
    public function checkConnection(SmtpSettings $smtpSettings)
    {
        $error = false;

        try {
            $mailer = $this->createMailerInstance($smtpSettings);
            $mailer->getTransport()->start();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return $error;
    }

    protected function createMailerInstance(SmtpSettings $smtpSettings)
    {
        $transport = \Swift_SmtpTransport::newInstance(
            $smtpSettings->getHost(),
            $smtpSettings->getPort(),
            $smtpSettings->getEncryption()
        );

        $transport->setUsername($smtpSettings->getUsername());
        $transport->setPassword($smtpSettings->getPassword());

        return \Swift_Mailer::newInstance($transport);
    }
}
