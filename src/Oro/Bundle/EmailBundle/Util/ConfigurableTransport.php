<?php

namespace Oro\Bundle\EmailBundle\Util;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Provider\AbstractSmtpSettingsProvider;

class ConfigurableTransport
{
    /**
     * @var AbstractSmtpSettingsProvider
     */
    private $provider;

    /**
     * @var \Swift_Transport
     */
    private $transport;

    /**
     * @param AbstractSmtpSettingsProvider $provider
     * @param \Swift_Transport $transport
     */
    public function __construct(
        AbstractSmtpSettingsProvider $provider,
        \Swift_Transport $transport
    ) {
        $this->provider = $provider;
        $this->transport = $transport;
    }

    /**
     * Get default transport with params updated from AbstractSmtpSettingsProvider (if possible)
     *
     * @return \Swift_Transport
     */
    public function getDefaultTransport()
    {
        //If it's SMTP, let's update configuration
        if ($this->transport instanceof \Swift_Transport_EsmtpTransport) {
            /** @var SmtpSettings $smtpSettings */
            $smtpSettings = $this->provider->getSmtpSettings();

            $this->updateTransportSetting($this->transport, 'setHost', $smtpSettings->getHost());
            $this->updateTransportSetting($this->transport, 'setPort', $smtpSettings->getPort());
            $this->updateTransportSetting($this->transport, 'setEncryption', $smtpSettings->getEncryption());
            $this->updateTransportSetting($this->transport, 'setUserName', $smtpSettings->getUsername());
            $this->updateTransportSetting($this->transport, 'setPassword', $smtpSettings->getPassword());
        }

        return $this->transport;
    }

    /**
     * @param \Swift_Transport_EsmtpTransport $transport
     * @param string $setter
     * @param mixed $value
     */
    private function updateTransportSetting(\Swift_Transport_EsmtpTransport $transport, $setter, $value)
    {
        if (!empty($value)) {
            $transport->$setter($value);
        }
    }
}
