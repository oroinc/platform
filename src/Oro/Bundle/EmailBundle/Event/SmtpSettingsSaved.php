<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Symfony\Contracts\EventDispatcher\Event;

class SmtpSettingsSaved extends Event
{
    const NAME = 'oro_email.smtp_settings_saved';

    /** @var SmtpSettings */
    protected $smtpSettings;

    public function __construct(SmtpSettings $smtpSettings)
    {
        $this->smtpSettings = $smtpSettings;
    }

    /**
     * @return SmtpSettings
     */
    public function getSmtpSettings()
    {
        return $this->smtpSettings;
    }
}
