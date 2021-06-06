<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImapBundle\DependencyInjection\Compiler\CredentialsNotificationSenderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CredentialsNotificationSenderPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var CredentialsNotificationSenderPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new CredentialsNotificationSenderPass();
    }

    public function testProcessNoMainService()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $issueManagerDef = $container->register('oro_imap.origin_credentials.issue_manager');

        $container->register('notification_sender_1')
            ->addTag('oro_imap.origin_credentials.notification_sender');
        $container->register('notification_sender_2')
            ->addTag('oro_imap.origin_credentials.notification_sender');

        $container->register('user_notification_sender_1')
            ->addTag('oro_imap.origin_credentials.user_notification_sender');
        $container->register('user_notification_sender_2')
            ->addTag('oro_imap.origin_credentials.user_notification_sender');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addNotificationSender', [new Reference('notification_sender_1')]],
                ['addNotificationSender', [new Reference('notification_sender_2')]],
                ['addUserNotificationSender', [new Reference('user_notification_sender_1')]],
                ['addUserNotificationSender', [new Reference('user_notification_sender_2')]]
            ],
            $issueManagerDef->getMethodCalls()
        );
    }

    public function testProcessWhenNoSenders()
    {
        $container = new ContainerBuilder();
        $issueManagerDef = $container->register('oro_imap.origin_credentials.issue_manager');

        $this->compiler->process($container);

        self::assertSame([], $issueManagerDef->getMethodCalls());
    }
}
