<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Sender\EmailTemplateSender;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;

/**
 * Sends template email to the specified user.
 */
class UserTemplateEmailSender
{
    private NotificationSettings $notificationSettingsModel;

    private EmailTemplateSender $emailTemplateSender;

    public function __construct(
        NotificationSettings $notificationSettingsModel,
        EmailTemplateSender $emailTemplateSender
    ) {
        $this->notificationSettingsModel = $notificationSettingsModel;
        $this->emailTemplateSender = $emailTemplateSender;
    }

    /**
     * @param EmailHolderInterface $user
     * @param string $emailTemplateName
     * @param array $emailTemplateParams
     * @param $scopeEntity
     * @return int
     */
    public function sendUserTemplateEmail(
        EmailHolderInterface $user,
        string $emailTemplateName,
        array $emailTemplateParams = [],
        $scopeEntity = null
    ): int {
        $from = $scopeEntity
            ? $this->notificationSettingsModel->getSenderByScopeEntity($scopeEntity)
            : $this->notificationSettingsModel->getSender();

        $emailUser = $this->emailTemplateSender->sendEmailTemplate(
            $from,
            $user,
            $emailTemplateName,
            $emailTemplateParams
        );

        return (int) ($emailUser !== null);
    }
}
