<?php

namespace Oro\Bundle\CalendarBundle\Notification;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface;

class EmailNotificationAdapter implements EmailNotificationInterface
{
    /**
     * @var EmailTemplateInterface
     */
    protected $template;

    /**
     * @var string[]
     */
    protected $recipients;

    /**
     * Constructor
     *
     * @param EmailTemplateInterface $template
     * @param                        $toEmail
     */
    public function __construct(EmailTemplateInterface $template, $toEmail)
    {
        $this->template = $template;
        $this->recipients = array($toEmail);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate($locale = null)
    {
        $template = $this->template;
        if (!is_null($locale)) {
            foreach ($template->getTranslations() as $translation) {
                if ($locale == $translation->getLocale()) {
                    $template->{'set' . ucfirst($translation->getField())}($translation->getContent());
                }
            }
            $template->setLocale($locale);
        }

        return $template;
    }

    /**
     * Gets email address of an user who owns a calendar
     *
     * @return string[]
     */
    public function getRecipientEmails()
    {
        return $this->recipients;
    }
}
