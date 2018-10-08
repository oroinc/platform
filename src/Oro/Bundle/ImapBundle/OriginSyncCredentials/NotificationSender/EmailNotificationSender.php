<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSenderInterface;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;

/**
 * Wrong credential sync email box notification sender channel that uses email messaging as the channel.
 */
class EmailNotificationSender implements NotificationSenderInterface
{
    /**
     * @var NotificationSettings
     */
    private $notificationSettingsModel;

    /**
     * @var TemplateEmailManager
     */
    private $templateEmailManager;

    /**
     * @param NotificationSettings $notificationSettingsModel
     * @param TemplateEmailManager $templateEmailManager
     */
    public function __construct(
        NotificationSettings $notificationSettingsModel,
        TemplateEmailManager $templateEmailManager
    ) {
        $this->notificationSettingsModel = $notificationSettingsModel;
        $this->templateEmailManager = $templateEmailManager;
    }

    /**
     * {@inheritdoc}
     */
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
            'host' => $emailOrigin->getImapHost()
        ];

        $this->templateEmailManager->sendTemplateEmail(
            $this->notificationSettingsModel->getSender(),
            [$sendTo],
            new EmailTemplateCriteria($templateName),
            $templateParameters
        );
    }
}
