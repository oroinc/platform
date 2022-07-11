<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\EventListener\NotificationAlertsListener;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\EmailBundle\Sync\NotificationAlertManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NotificationAlertsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var NotificationAlertManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationAlertManager;

    /** @var mixed|MailboxManager|\PHPUnit\Framework\MockObject\MockObject */
    private $mailboxManager;

    /** @var mixed|TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var FlashBag */
    private $flashbag;

    /** @var NotificationAlertsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->notificationAlertManager = $this->createMock(NotificationAlertManager::class);
        $this->mailboxManager = $this->createMock(MailboxManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->flashbag = new FlashBag();

        $this->session->expects(self::any())
            ->method('getFlashBag')
            ->willReturn($this->flashbag);

        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($str) {
                return 'translated_' . $str;
            });

        $this->listener = new NotificationAlertsListener(
            $this->router,
            $this->session,
            $this->translator,
            $this->notificationAlertManager,
            $this->mailboxManager,
            $this->tokenAccessor
        );
    }

    public function testOnRequestOnNonMasterRequest(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request([], [], ['_route' => 'test']),
            HttpKernelInterface::SUB_REQUEST
        );

        $this->notificationAlertManager->expects(self::never())
            ->method('hasNotificationAlertsByType');

        $this->listener->onRequest($event);

        self::assertEmpty($this->flashbag->all());
    }

    public function testOnRequestOnNonConfigPage(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request([], [], ['_route' => 'test']),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->notificationAlertManager->expects(self::never())
            ->method('hasNotificationAlertsByType');

        $this->listener->onRequest($event);

        self::assertEmpty($this->flashbag->all());
    }

    public function testOnRequestOnSystemEmailConfigPageWithoutAlerts(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route'         => 'oro_config_configuration_system',
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'email_configuration'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(456);

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByTypeForUserAndOrganization')
            ->willReturn([]);

        $this->listener->onRequest($event);

        self::assertEmpty($this->flashbag->all());
    }

    public function testOnRequestOnSystemEmailConfigPageWithAlerts(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route'         => 'oro_config_configuration_system',
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'email_configuration'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(456);

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByTypeForUserAndOrganization')
            ->with(null, 456)
            ->willReturn([
                EmailSyncNotificationAlert::ALERT_TYPE_SYNC          => 1,
                EmailSyncNotificationAlert::ALERT_TYPE_AUTH          => 1,
                EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER => 1
            ]);

        $this->listener->onRequest($event);

        self::assertEquals(
            ['warning' => [
                'translated_oro.email.sync_alert.system_origin.auth',
                'translated_oro.email.sync_alert.system_origin.switch_folder',
                'translated_oro.email.sync_alert.system_origin.sync',
            ]],
            $this->flashbag->all()
        );
    }

    public function testOnRequestOnSystemEmailConfigPageWithRefreshTokensAlerts(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route'         => 'oro_config_configuration_system',
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'email_configuration'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(456);

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByTypeForUserAndOrganization')
            ->with(null, 456)
            ->willReturn([
                EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN => 1
            ]);

        $this->listener->onRequest($event);

        self::assertEquals(
            ['warning' => [
                'translated_oro.email.sync_alert.system_origin.auth'
            ]],
            $this->flashbag->all()
        );
    }

    public function testOnRequestOnMyEmailConfigPageWithoutAlerts(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route'         => 'oro_user_profile_configuration',
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'user_email_configuration'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByType')
            ->willReturn([]);

        $this->listener->onRequest($event);

        self::assertEmpty($this->flashbag->all());
    }

    public function testOnRequestOnMyEmailConfigPageWithAuthAlerts(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route'         => 'oro_user_profile_configuration',
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'user_email_configuration'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByType')
            ->willReturn([
                EmailSyncNotificationAlert::ALERT_TYPE_AUTH          => 1,
                EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER => 0,
                'another_type'                                       => 10
            ]);

        $this->listener->onRequest($event);

        self::assertEquals(['warning' => ['translated_oro.email.sync_alert.auth.short']], $this->flashbag->all());
    }

    public function testOnRequestOnMyEmailConfigPageWithRefreshTokenAuthAlerts(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route'         => 'oro_user_profile_configuration',
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'user_email_configuration'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByType')
            ->willReturn([
                EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN => 1
            ]);

        $this->listener->onRequest($event);

        self::assertEquals(['warning' => ['translated_oro.email.sync_alert.auth.short']], $this->flashbag->all());
    }

    public function testOnRequestOnMyEmailConfigPageWithSwitchFolderAlerts(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route'         => 'oro_user_profile_configuration',
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'user_email_configuration'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByType')
            ->willReturn([
                EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER => 1,
                'another_type'                                       => 10
            ]);

        $this->listener->onRequest($event);

        self::assertEquals(
            ['warning' => ['translated_oro.email.sync_alert.switch_folder.short']],
            $this->flashbag->all()
        );
    }

    public function testOnRequestOnMyEmailConfigPageWithAuthAndSwitchFolderAlerts(): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route'         => 'oro_user_profile_configuration',
                    'activeGroup'    => 'platform',
                    'activeSubGroup' => 'user_email_configuration'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByType')
            ->willReturn([
                EmailSyncNotificationAlert::ALERT_TYPE_AUTH          => 1,
                EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER => 1
            ]);

        $this->listener->onRequest($event);

        self::assertEquals(
            ['warning' => [
                'translated_oro.email.sync_alert.auth.short',
                'translated_oro.email.sync_alert.switch_folder.short',
            ]],
            $this->flashbag->all()
        );
    }

    public function testOnRequestOnMyEmailsPageWithoutSystemMailboxesAndEmptyCurrentBoxAlerts(): void
    {
        $user = new User();
        $user->setId(48);

        $organization = new Organization();
        $organization->setId(23);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route' => 'oro_email_user_emails'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->mailboxManager->expects(self::once())
            ->method('findAvailableMailboxes')
            ->with($user, $organization)
            ->willReturn([]);

        $this->notificationAlertManager->expects(self::never())
            ->method('getNotificationAlertsCountGroupedByUserAndType');
        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByType')
            ->willReturn([]);

        $this->listener->onRequest($event);

        self::assertEmpty($this->flashbag->all());
    }

    public function testOnRequestOnMyEmailsPageWithoutSystemMailboxesAndCurrentBoxAlerts(): void
    {
        $user = new User();
        $user->setId(48);

        $organization = new Organization();
        $organization->setId(23);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route' => 'oro_email_user_emails'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->mailboxManager->expects(self::once())
            ->method('findAvailableMailboxes')
            ->with($user, $organization)
            ->willReturn([]);

        $this->router->expects(self::exactly(2))
            ->method('generate');

        $this->notificationAlertManager->expects(self::never())
            ->method('getNotificationAlertsCountGroupedByUserAndType');
        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByType')
            ->willReturn([
                EmailSyncNotificationAlert::ALERT_TYPE_AUTH          => 1,
                EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER => 1,
                EmailSyncNotificationAlert::ALERT_TYPE_SYNC          => 2
            ]);

        $this->listener->onRequest($event);

        self::assertEquals(
            ['warning' => [
                'translated_oro.email.sync_alert.sync',
                'translated_oro.email.sync_alert.auth.full',
                'translated_oro.email.sync_alert.switch_folder.full'
            ]],
            $this->flashbag->all()
        );
    }

    public function testOnRequestOnMyEmailsPageWithoutSystemMailboxesAndCurrentBoxRefreshTokenAlerts(): void
    {
        $user = new User();
        $user->setId(48);

        $organization = new Organization();
        $organization->setId(23);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route' => 'oro_email_user_emails'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->mailboxManager->expects(self::once())
            ->method('findAvailableMailboxes')
            ->with($user, $organization)
            ->willReturn([]);

        $this->router->expects(self::once())
            ->method('generate');

        $this->notificationAlertManager->expects(self::never())
            ->method('getNotificationAlertsCountGroupedByUserAndType');
        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByType')
            ->willReturn([
                EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN => 1
            ]);

        $this->listener->onRequest($event);

        self::assertEquals(
            ['warning' => [
                'translated_oro.email.sync_alert.auth.full'
            ]],
            $this->flashbag->all()
        );
    }

    public function testOnRequestOnMyEmailsPageWithSystemMailboxesAndAlerts(): void
    {
        $user = new User();
        $user->setId(48);

        $organization = new Organization();
        $organization->setId(23);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route' => 'oro_email_user_emails'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->mailboxManager->expects(self::once())
            ->method('findAvailableMailboxes')
            ->with($user, $organization)
            ->willReturn([new Mailbox()]);

        $this->router->expects(self::exactly(3))
            ->method('generate');

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByUserAndType')
            ->willReturn([
                0 => [EmailSyncNotificationAlert::ALERT_TYPE_AUTH => 1],
                48 => [
                    EmailSyncNotificationAlert::ALERT_TYPE_AUTH          => 1,
                    EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER => 1,
                    EmailSyncNotificationAlert::ALERT_TYPE_SYNC          => 2
                ]
            ]);
        $this->notificationAlertManager->expects(self::never())
            ->method('getNotificationAlertsCountGroupedByType');

        $this->listener->onRequest($event);

        self::assertEquals(
            ['warning' => [
                'translated_oro.email.sync_alert.system_origin.common',
                'translated_oro.email.sync_alert.sync',
                'translated_oro.email.sync_alert.auth.full',
                'translated_oro.email.sync_alert.switch_folder.full'
            ]],
            $this->flashbag->all()
        );
    }

    public function testOnRequestOnMyEmailsPageWithSystemMailboxesAndWithoutCurrentBoxAlerts(): void
    {
        $user = new User();
        $user->setId(88);

        $organization = new Organization();
        $organization->setId(77);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route' => 'oro_email_user_emails'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->mailboxManager->expects(self::once())
            ->method('findAvailableMailboxes')
            ->with($user, $organization)
            ->willReturn([new Mailbox()]);

        $this->router->expects(self::once())
            ->method('generate');

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByUserAndType')
            ->willReturn([
                0 => [EmailSyncNotificationAlert::ALERT_TYPE_AUTH => 1]
            ]);
        $this->notificationAlertManager->expects(self::never())
            ->method('getNotificationAlertsCountGroupedByType');

        $this->listener->onRequest($event);

        self::assertEquals(
            ['warning' => [
                'translated_oro.email.sync_alert.system_origin.common'
            ]],
            $this->flashbag->all()
        );
    }

    public function testOnRequestOnMyEmailsPageWithSystemMailboxesAndWithoutAlerts(): void
    {
        $user = new User();
        $user->setId(23);

        $organization = new Organization();
        $organization->setId(45);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(
                [],
                [],
                [
                    '_route' => 'oro_email_user_emails'
                ]
            ),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->mailboxManager->expects(self::once())
            ->method('findAvailableMailboxes')
            ->with($user, $organization)
            ->willReturn([new Mailbox()]);

        $this->router->expects(self::never())
            ->method('generate');

        $this->notificationAlertManager->expects(self::once())
            ->method('getNotificationAlertsCountGroupedByUserAndType')
            ->willReturn([]);
        $this->notificationAlertManager->expects(self::never())
            ->method('getNotificationAlertsCountGroupedByType');

        $this->listener->onRequest($event);

        self::assertEmpty($this->flashbag->all());
    }
}
