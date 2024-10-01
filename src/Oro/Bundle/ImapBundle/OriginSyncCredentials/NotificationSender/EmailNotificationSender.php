<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\EmailBundle\Sender\EmailTemplateSender;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSenderInterface;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;

/**
 * Wrong credential sync email box notification sender channel that uses email messaging as the channel.
 */
class EmailNotificationSender implements NotificationSenderInterface
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

    #[\Override]
    public function sendNotification(UserEmailOrigin $emailOrigin)
    {
        $originOwner = $emailOrigin->getOwner();
        if ($originOwner) {
            $templateName = 'sync_wrong_credentials_user_box';
            $sendTo = $emailOrigin->getOwner();
        } else {
            $templateName = 'sync_wrong_credentials_system_box';
            $sendTo = $emailOrigin->getMailbox();
        }

        $templateParameters = [
            'username' => $emailOrigin->getUser(),
            'host' => $emailOrigin->getImapHost(),
        ];

        $this->emailTemplateSender->sendEmailTemplate(
            $this->notificationSettingsModel->getSender(),
            $sendTo,
            $templateName,
            $templateParameters
        );
    }
}
