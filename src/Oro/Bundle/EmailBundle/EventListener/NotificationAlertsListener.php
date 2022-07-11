<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks for existing notification alerts generated during email sync and notifies user
 * on `System Configuration`|`My Configuration` -> `Email Configuration` and `My emails` pages.
 */
class NotificationAlertsListener
{
    private RouterInterface $router;
    private Session $session;
    private TranslatorInterface $translator;
    private NotificationAlertManager $notificationAlertManager;
    private MailboxManager $mailboxManager;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        RouterInterface          $router,
        Session                  $session,
        TranslatorInterface      $translator,
        NotificationAlertManager $notificationAlertManager,
        MailboxManager           $mailboxManager,
        TokenAccessorInterface   $tokenAccessor
    ) {
        $this->router = $router;
        $this->session = $session;
        $this->translator = $translator;
        $this->notificationAlertManager = $notificationAlertManager;
        $this->mailboxManager = $mailboxManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }

        $messages = [];

        $request = $event->getRequest();
        if ($this->isSystemEmailSettingsPage($request)) {
            $messages = $this->getNotificationMessagesFromSystemEmailSettingsPage();
        } elseif ($this->isMyEmailSettingsPage($request)) {
            $messages = $this->getNotificationMessagesFromMyEmailSettingsPage();
        } elseif ($this->isMyEmailsPage($request)) {
            $messages = $this->getNotificationMessagesFromMyEmailsPage();
        }

        if (!empty($messages)) {
            $this->showNotificationMessages($messages);
        }
    }

    private function isSystemEmailSettingsPage(Request $request): bool
    {
        $requestAttributes = $request->attributes->all();

        return
            'oro_config_configuration_system' === $requestAttributes['_route']
            && 'platform' === $requestAttributes['activeGroup']
            && 'email_configuration' === $requestAttributes['activeSubGroup'];
    }

    private function isMyEmailSettingsPage(Request $request): bool
    {
        $requestAttributes = $request->attributes->all();

        return
            'oro_user_profile_configuration' === $requestAttributes['_route']
            && 'platform' === $requestAttributes['activeGroup']
            && 'user_email_configuration' === $requestAttributes['activeSubGroup'];
    }

    private function isMyEmailsPage(Request $request): bool
    {
        return 'oro_email_user_emails' === $request->attributes->all()['_route'];
    }

    /**
     * @return string[]
     */
    private function getNotificationMessagesFromSystemEmailSettingsPage(): array
    {
        $messages = [];
        $alerts = $this->notificationAlertManager->getNotificationAlertsCountGroupedByTypeForUserAndOrganization(
            null,
            $this->tokenAccessor->getOrganizationId()
        );

        if (!empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_AUTH])
            || !empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN])
        ) {
            $messages[] = $this->translator->trans('oro.email.sync_alert.system_origin.auth');
        }
        if (!empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER])) {
            $messages[] = $this->translator->trans('oro.email.sync_alert.system_origin.switch_folder');
        }
        if (!empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_SYNC])) {
            $messages[] = $this->translator->trans('oro.email.sync_alert.system_origin.sync');
        }

        return $messages;
    }

    /**
     * @return string[]
     */
    private function getNotificationMessagesFromMyEmailSettingsPage(): array
    {
        $messages = [];
        $alerts = $this->notificationAlertManager->getNotificationAlertsCountGroupedByType();
        if (!empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_AUTH])
            || !empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN])
        ) {
            $messages[] = $this->translator->trans('oro.email.sync_alert.auth.short');
        }
        if (!empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER])) {
            $messages[] = $this->translator->trans('oro.email.sync_alert.switch_folder.short');
        }

        return $messages;
    }

    /**
     * @return string[]
     */
    private function getNotificationMessagesFromMyEmailsPage(): array
    {
        $messages = [];

        $systemMailboxes = $this->mailboxManager->findAvailableMailboxes(
            $this->tokenAccessor->getUser(),
            $this->tokenAccessor->getOrganization()
        );

        // if user have some system mailboxes, show system mail boxes notification messages as well
        // as user email messages alerts.
        if (count($systemMailboxes) > 0) {
            $alertsByOrigin = $this->notificationAlertManager->getNotificationAlertsCountGroupedByUserAndType();
            if (\array_key_exists(0, $alertsByOrigin)) {
                $messages[] = $this->translator->trans('oro.email.sync_alert.system_origin.common', [
                    '%settings_url%' => $this->router->generate('oro_config_configuration_system', [
                        'activeGroup'    => 'platform',
                        'activeSubGroup' => 'email_configuration'
                    ])
                ]);
                unset($alertsByOrigin[0]);
            }
            $alerts = array_shift($alertsByOrigin) ?? [];
        } else {
            $alerts = $this->notificationAlertManager->getNotificationAlertsCountGroupedByType();
        }

        return array_merge(
            $messages,
            $this->getNotificationMessagesFromMyEmailsPageForUserOrigins($alerts)
        );
    }

    private function getNotificationMessagesFromMyEmailsPageForUserOrigins(array $alerts): array
    {
        $messages = [];

        if (!empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_SYNC])) {
            $messages[] = $this->translator->trans('oro.email.sync_alert.sync');
        }
        if (!empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_AUTH])
            || !empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN])
        ) {
            $messages[] = $this->translator->trans('oro.email.sync_alert.auth.full', [
                '%settings_url%' => $this->router->generate('oro_user_profile_configuration', [
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'user_email_configuration'
                ])
            ]);
        }
        if (!empty($alerts[EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER])) {
            $messages[] = $this->translator->trans('oro.email.sync_alert.switch_folder.full', [
                '%settings_url%' => $this->router->generate('oro_user_profile_configuration', [
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'user_email_configuration'
                ])
            ]);
        }

        return $messages;
    }

    /**
     * @param string[] $messages
     */
    private function showNotificationMessages(array $messages): void
    {
        $flashBag = $this->session->getFlashBag();
        foreach ($messages as $message) {
            $flashBag->add('warning', $message);
        }
    }
}
