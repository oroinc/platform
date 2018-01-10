<?php

namespace Oro\Bundle\EmailBundle\EmailSyncCredentials\NotificationSender;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\EmailSyncCredentials\NotificationSenderInterface;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * Wrong credential sync email box notification sender channel that uses user flashbag messages as the channel.
 */
class FlashBagNotificationSender implements NotificationSenderInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     */
    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function sendNotification(UserEmailOrigin $emailOrigin)
    {
        if ($emailOrigin->getOwner()) {
            $notificationMessage = 'oro.email.sync.flash_message.user_box_failed';
        } else {
            $notificationMessage = 'oro.email.sync.flash_message.system_box_failed';
        }

        $translatedMessage = $this->translator->trans(
            $notificationMessage,
            [
                '%username%' => $emailOrigin->getUser(),
                '%host%' => $emailOrigin->getImapHost()
            ]
        );

        $this->requestStack->getCurrentRequest()->getSession()->getFlashBag()->add(
            'error',
            $translatedMessage
        );
    }
}
