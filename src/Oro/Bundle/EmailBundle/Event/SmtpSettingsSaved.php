<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when SMTP settings are saved.
 *
 * This event is triggered after SMTP configuration has been successfully saved,
 * allowing listeners to perform additional processing such as connection validation or cache clearing.
 */
class SmtpSettingsSaved extends Event
{
    public const NAME = 'oro_email.smtp_settings_saved';

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
